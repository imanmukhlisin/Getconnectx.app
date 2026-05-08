# Backend handoff: direct chat room creation owned by backend, messaging owned by client via Supabase

## Decision

For ConnectX direct chat, backend owns the business event that creates chat access, while client owns chat operations.

**Chosen architecture:**

* Backend creates `chat_rooms` and inserts both users into `chat_room_members` when a Tinder-style match happens, and insert messages
* Client fetches `conversation_summaries` and `messages` directly from Supabase.
* Client inserts new `messages` to backend
* Database triggers/RPCs maintain `conversation_summaries`, unread counts, and read state.
* Supabase Realtime delivers message inserts, summary updates, typing, and presence.

This keeps business logic in backend and keeps realtime chat low-latency without forcing every message through the custom backend.

# API CONTRACT ( MESSAGE SEND) 

POST /api/chat/messages

```
Body: 
{
  "room_id": "uuid",
  "client_id": "string",
  "message_type": "image | text",
  "content": "optional caption",
  "media_url": "https://cdn.example.com/chat/abc.jpg",
  "thumbnail_url": "https://cdn.example.com/chat/abc-thumb.jpg",
  "media_name": "photo.jpg",
  "media_mime_type": "image/jpeg",
  "media_size_bytes": 245761
}
```

## Success Response

```
{
  "data": {
    "id": "uuid",
    "room_id": "uuid",
    "sender_id": "uuid",
    "client_id": "string",
    "content": "hello",
    "created_at": "2026-04-14T08:00:00.000Z",
    "message_type": "text",
    "media_url": null,
    "media_mime_type": null,
    "media_name": null,
    "media_size_bytes": null,
    "thumbnail_url": null
  }
}
```

## Why we chose this

### Backend should own match logic and message delivery

A match is a protected business rule, so the client must not be trusted to create direct rooms or participants.

Backend decides:

* whether users are truly matched
* whether a room already exists
* whether room creation should happen immediately on match
* whether retries should be ignored safely

### Client should own messaging UX

Once membership exists, chat is mostly a data and realtime problem.

Client can:

* fetch inbox from `conversation_summaries`
* fetch paginated room history from `messages`
* insert messages directly into `messages`
* subscribe to realtime updates
* mark a conversation as read through RPC

This is a good fit for Supabase and keeps latency low.

## Responsibility split

### Backend

* detect or confirm a match
* create one direct room per matched pair
* insert both users into `chat_room_members`
* keep `public.users` profile data up to date
* prevent duplicate room creation across retries or race conditions

### Client

* read `conversation_summaries` for inbox/chat list
* read paginated `messages` for the active room
* insert new `messages`
* call read RPC such as `mark_conversation_read(conversation_uuid)`
* subscribe to realtime for summaries, active-room messages, typing, and presence

### Database / Supabase

* enforce membership access with RLS
* maintain `conversation_summaries`
* update `last_message_*` and `unread_count` on new message inserts
* clear unread state on read RPC
* push changes through Supabase Realtime

## Non-negotiable implementation rules

### 1\. One direct room per matched pair

Room creation must be idempotent.

Backend must guarantee that retries, duplicate match events, or concurrent requests do not create multiple direct rooms for the same two users.

Recommended approaches:

* store a normalized pair key on the direct room
* or store the room id on the match record and reuse it

### 2\. Client does not create summaries

`conversation_summaries` is DB-managed only.

The client must never insert or update:

* summary rows
* unread counts
* last message preview
* participant metadata in summaries

### 3\. Security is enforced with RLS

At minimum:

* a user can read messages only for rooms they belong to
* a user can insert messages only for rooms they belong to
* a user can read summaries only where `conversation_summaries.user_id = auth.uid()`
* a user cannot directly write summaries
* a user can read room membership only where they are also a member, if membership is exposed to the client

### 4\. Subscription strategy

Do not subscribe to message streams for every room in the inbox.

Recommended client behavior:

* subscribe to `conversation_summaries` for inbox updates
* subscribe to `messages` only for the currently opened room
* use broadcast/presence only for the current room

## Proposed data contract

### Core tables

* `public.chat_rooms`
* `public.chat_room_members`
* `public.messages`
* `public.conversation_summaries`
* `public.users` as participant metadata source

### `public.chat_rooms`

One row per conversation.

Suggested fields:

* `id uuid primary key`
* `type text check (type in ('direct', 'group'))`
* `title text not null`
* `created_at timestamptz default now()`

### `public.chat_room_members`

Membership table linking users to rooms.

Suggested fields:

* `room_id uuid not null references public.chat_rooms(id) on delete cascade`
* `user_id uuid not null references auth.users(id) on delete cascade`
* `joined_at timestamptz default now()`
* primary key `(room_id, user_id)`

### `public.messages`

Durable message history.

Suggested fields:

* `id uuid primary key`
* `room_id uuid not null references public.chat_rooms(id) on delete cascade`
* `sender_id uuid not null references auth.users(id) on delete restrict`
* `client_id text null` for optimistic-send deduplication
* `content text not null`
* `message_type text not null default 'text'`
* `created_at timestamptz default now()`

Optional media fields:

* `media_url text null`
* `media_mime_type text null`
* `media_name text null`
* `media_size_bytes bigint null`
* `thumbnail_url text null`

### `public.conversation_summaries`

One summary row per user per conversation for inbox rendering.

Suggested fields:

* `conversation_id uuid not null references public.chat_rooms(id) on delete cascade`
* `user_id uuid not null references auth.users(id) on delete cascade`
* `title text not null`
* `kind text not null check (kind in ('direct', 'group'))`
* `last_message_id uuid null references public.messages(id) on delete set null`
* `last_message_text text null`
* `last_message_at timestamptz null`
* `unread_count integer not null default 0`
* `last_read_message_id uuid null references public.messages(id) on delete set null`
* `last_read_at timestamptz null`
* `updated_at timestamptz not null default now()`
* primary key `(user_id, conversation_id)`

Required direct-chat participant metadata:

* `participant_user_id uuid null references auth.users(id) on delete set null`
* `participant_name text null`
* `participant_photo_url text null`
* `participant_headline text null`

### `public.users`

Profile source for chat participant metadata.

Expected fields at minimum:

* `id uuid primary key`
* `name text`
* `photo_url text`
* `headline text`

# Sample table reference for backend for ( messages, conversation_summaries, chat_rooms, and chat_room_members, DB Triggers and other functions)

```
-- ============================================================
-- EXTENSIONS
-- ============================================================
create extension if not exists pgcrypto;

-- ============================================================
-- TABLES
-- ============================================================

-- CHAT ROOMS
create table if not exists public.chat_rooms (
  id         uuid primary key default gen_random_uuid(),
  type       text not null check (type in ('direct', 'group')),
  title      text not null,
  created_at timestamptz not null default now()
);

-- CHAT ROOM MEMBERS
create table if not exists public.chat_room_members (
  room_id   uuid not null references public.chat_rooms(id) on delete cascade,
  user_id   uuid not null references auth.users(id) on delete cascade,
  joined_at timestamptz not null default now(),
  primary key (room_id, user_id)
);

-- MESSAGES
create table if not exists public.messages (
  id               uuid primary key default gen_random_uuid(),
  room_id          uuid not null references public.chat_rooms(id) on delete cascade,
  sender_id        uuid not null references auth.users(id) on delete restrict,
  client_id        text null,
  message_type     text not null default 'text',
  content          text null,
  media_url        text null,
  media_mime_type  text null,
  media_name       text null,
  media_size_bytes bigint null,
  thumbnail_url    text null,
  created_at       timestamptz not null default now(),

  constraint messages_message_type_check
    check (message_type in ('text', 'image', 'video', 'file')),

  constraint messages_payload_check
    check (
      (message_type = 'text' and content is not null and btrim(content) <> '')
      or
      (message_type in ('image', 'video', 'file') and media_url is not null)
    )
);

-- PROFILES
create table if not exists public.profiles (
  id         uuid primary key references auth.users(id) on delete cascade,
  name       text not null,
  photo_url  text null,
  headline   text null,
  updated_at timestamptz not null default now()
);

-- CONVERSATION SUMMARIES
create table if not exists public.conversation_summaries (
  conversation_id             uuid not null references public.chat_rooms(id) on delete cascade,
  user_id                     uuid not null references auth.users(id) on delete cascade,
  title                       text null,
  kind                        text null,
  last_message_id             uuid null,
  last_message_text           text null,
  last_message_at             timestamptz null,
  last_read_message_id uuid null references messages(id) on delete set null,
  last_read_at timestamptz null,
  unread_count                int not null default 0,
  updated_at                  timestamptz not null default now(),
  participant_user_id         uuid null references auth.users(id) on delete set null,
  participant_name            text null,
  participant_photo_url       text null,
  participant_headline        text null,
  participant_whatsapp_number text null,

  primary key (user_id, conversation_id)
);

-- ============================================================
-- INDEXES
-- ============================================================
create index if not exists idx_messages_room_created_at
  on public.messages(room_id, created_at desc);

create unique index if not exists idx_messages_sender_client_id
  on public.messages(sender_id, client_id)
  where client_id is not null;

create index if not exists idx_conversation_summaries_user_participant
  on public.conversation_summaries(user_id, participant_user_id);

-- ============================================================
-- RLS
-- ============================================================
alter table public.chat_rooms enable row level security;
alter table public.chat_room_members enable row level security;
alter table public.messages enable row level security;
alter table public.profiles enable row level security;

-- ============================================================
-- POLICIES
-- ============================================================

-- chat_rooms
drop policy if exists "members can read their rooms" on public.chat_rooms;
create policy "members can read their rooms"
on public.chat_rooms
for select
to authenticated
using (
  exists (
    select 1
    from public.chat_room_members m
    where m.room_id = chat_rooms.id
      and m.user_id = auth.uid()
  )
);

-- chat_room_members
drop policy if exists "members can read their memberships" on public.chat_room_members;
create policy "members can read their memberships"
on public.chat_room_members
for select
to authenticated
using (user_id = auth.uid());

-- messages
drop policy if exists "members can read messages in their rooms" on public.messages;
create policy "members can read messages in their rooms"
on public.messages
for select
to authenticated
using (
  exists (
    select 1
    from public.chat_room_members m
    where m.room_id = messages.room_id
      and m.user_id = auth.uid()
  )
);

drop policy if exists "members can send messages to their rooms" on public.messages;
create policy "members can send messages to their rooms"
on public.messages
for insert
to authenticated
with check (
  sender_id = auth.uid()
  and exists (
    select 1
    from public.chat_room_members m
    where m.room_id = messages.room_id
      and m.user_id = auth.uid()
  )
);

-- profiles
drop policy if exists "users can read their own profile" on public.profiles;
create policy "users can read their own profile"
on public.profiles
for select
to authenticated
using (id = auth.uid());

drop policy if exists "chat members can read shared profiles" on public.profiles;
create policy "chat members can read shared profiles"
on public.profiles
for select
to authenticated
using (
  id = auth.uid()
  or exists (
    select 1
    from public.chat_room_members viewer
    join public.chat_room_members other
      on other.room_id = viewer.room_id
    where viewer.user_id = auth.uid()
      and other.user_id = profiles.id
  )
);

drop policy if exists "users can insert their own profile" on public.profiles;
create policy "users can insert their own profile"
on public.profiles
for insert
to authenticated
with check (id = auth.uid());

drop policy if exists "users can update their own profile" on public.profiles;
create policy "users can update their own profile"
on public.profiles
for update
to authenticated
using (id = auth.uid())
with check (id = auth.uid());

-- ============================================================
-- FUNCTIONS
-- ============================================================

create or replace function public.set_profile_updated_at()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
begin
  new.updated_at = now();
  return new;
end;
$$;

create or replace function public.handle_auth_user_created_profile()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
begin
  insert into public.profiles (id, name, photo_url)
  values (
    new.id,
    coalesce(
      new.raw_user_meta_data ->> 'full_name',
      new.raw_user_meta_data ->> 'name',
      split_part(coalesce(new.email, ''), '@', 1),
      'ConnectX Member'
    ),
    new.raw_user_meta_data ->> 'avatar_url'
  )
  on conflict (id) do nothing;

  return new;
end;
$$;

create or replace function public.sync_conversation_summary_for_member(
  member_room_id uuid,
  member_user_id uuid
)
returns void
language plpgsql
security definer
set search_path = public
as $$
declare
  room_record                    public.chat_rooms%rowtype;
  latest_message_record          record;
  other_member_id                uuid;
  other_profile_name             text;
  other_profile_photo_url        text;
  other_profile_headline         text;
  other_profile_whatsapp_number  text;
begin
  select *
  into room_record
  from public.chat_rooms
  where id = member_room_id;

  if not found then
    return;
  end if;

  select m.id, m.content, m.created_at
  into latest_message_record
  from public.messages m
  where m.room_id = member_room_id
  order by m.created_at desc
  limit 1;

  other_member_id               := null;
  other_profile_name            := null;
  other_profile_photo_url       := null;
  other_profile_headline        := null;
  other_profile_whatsapp_number := null;

  if room_record.type = 'direct' then
    select crm.user_id
    into other_member_id
    from public.chat_room_members crm
    where crm.room_id = member_room_id
      and crm.user_id <> member_user_id
    order by crm.joined_at asc
    limit 1;

    if other_member_id is not null then
      select p.name, p.photo_url, p.headline
      into other_profile_name, other_profile_photo_url, other_profile_headline
      from public.profiles p
      where p.id = other_member_id;

      select u.whatsapp_number
      into other_profile_whatsapp_number
      from public.users u
      where u.id = other_member_id;
    end if;
  end if;

  insert into public.conversation_summaries (
    conversation_id,
    user_id,
    title,
    kind,
    last_message_id,
    last_message_text,
    last_message_at,
    unread_count,
    updated_at,
    participant_user_id,
    participant_name,
    participant_photo_url,
    participant_headline,
    participant_whatsapp_number
  )
  values (
    member_room_id,
    member_user_id,
    room_record.title,
    room_record.type,
    latest_message_record.id,
    latest_message_record.content,
    latest_message_record.created_at,
    0,
    coalesce(latest_message_record.created_at, now()),
    other_member_id,
    coalesce(other_profile_name, case when room_record.type = 'direct' then room_record.title else null end),
    other_profile_photo_url,
    other_profile_headline,
    other_profile_whatsapp_number
  )
  on conflict (user_id, conversation_id) do update
  set
    title                       = excluded.title,
    kind                        = excluded.kind,
    last_message_id             = excluded.last_message_id,
    last_message_text           = excluded.last_message_text,
    last_message_at             = excluded.last_message_at,
    updated_at                  = greatest(public.conversation_summaries.updated_at, excluded.updated_at),
    participant_user_id         = excluded.participant_user_id,
    participant_name            = excluded.participant_name,
    participant_photo_url       = excluded.participant_photo_url,
    participant_headline        = excluded.participant_headline,
    participant_whatsapp_number = excluded.participant_whatsapp_number;
end;
$$;

create or replace function public.handle_message_insert_update_summaries()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
declare
  summary_preview text;
begin
  summary_preview := case
    when new.message_type = 'image' then coalesce(nullif(btrim(new.content), ''), 'Sent a photo')
    when new.message_type = 'video' then coalesce(nullif(btrim(new.content), ''), 'Sent a video')
    when new.message_type = 'file'  then coalesce(nullif(btrim(new.content), ''), 'Sent a file')
    else coalesce(new.content, 'New message')
  end;

  update public.conversation_summaries
  set
    last_message_id   = new.id,
    last_message_text = summary_preview,
    last_message_at   = new.created_at,
    unread_count = case
      when user_id = new.sender_id then 0
      else unread_count + 1
    end,
    updated_at = new.created_at
  where conversation_id = new.room_id;

  return new;
end;
$$;

create or replace function public.handle_chat_room_member_insert()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
declare
  member_record record;
begin
  for member_record in
    select crm.user_id
    from public.chat_room_members crm
    where crm.room_id = new.room_id
  loop
    perform public.sync_conversation_summary_for_member(new.room_id, member_record.user_id);
  end loop;

  return new;
end;
$$;

create or replace function public.handle_chat_room_member_delete()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
declare
  member_record record;
begin
  delete from public.conversation_summaries
  where conversation_id = old.room_id
    and user_id = old.user_id;

  for member_record in
    select crm.user_id
    from public.chat_room_members crm
    where crm.room_id = old.room_id
  loop
    perform public.sync_conversation_summary_for_member(old.room_id, member_record.user_id);
  end loop;

  return old;
end;
$$;

create or replace function public.handle_chat_room_update_sync_summaries()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
declare
  member_record record;
begin
  for member_record in
    select crm.user_id
    from public.chat_room_members crm
    where crm.room_id = new.id
  loop
    perform public.sync_conversation_summary_for_member(new.id, member_record.user_id);
  end loop;

  return new;
end;
$$;

create or replace function public.mark_conversation_read(conversation_uuid uuid)
returns void
language plpgsql
security definer
set search_path = public
as $$
declare
  latest_message_record record;
begin
  if auth.uid() is null then
    raise exception 'Authentication required';
  end if;

  if not exists (
    select 1
    from public.chat_room_members
    where room_id = conversation_uuid
      and user_id = auth.uid()
  ) then
    raise exception 'Not a member of this conversation';
  end if;

  select
    m.id,
    m.created_at
  into latest_message_record
  from public.messages m
  where m.room_id = conversation_uuid
  order by m.created_at desc
  limit 1;

  update public.conversation_summaries
  set
    unread_count = 0,
    last_read_message_id = latest_message_record.id,
    last_read_at = now(),
    updated_at = greatest(updated_at, now())
  where conversation_id = conversation_uuid
    and user_id = auth.uid();
end;
$$;

create or replace function public.handle_profile_update_sync_conversation_summaries()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
begin
  update public.conversation_summaries cs
  set
    participant_name      = new.name,
    participant_photo_url = new.photo_url,
    participant_headline  = new.headline,
    updated_at            = greatest(cs.updated_at, now())
  from public.chat_room_members viewer
  join public.chat_room_members participant
    on participant.room_id = viewer.room_id
   and participant.user_id = new.id
   and participant.user_id <> viewer.user_id
  join public.chat_rooms cr
    on cr.id = viewer.room_id
  where cr.type = 'direct'
    and cs.conversation_id = viewer.room_id
    and cs.user_id = viewer.user_id;

  return new;
end;
$$;

create or replace function public.handle_user_update_sync_conversation_summaries()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
begin
  update public.conversation_summaries cs
  set
    participant_whatsapp_number = new.whatsapp_number,
    updated_at                  = greatest(cs.updated_at, now())
  from public.chat_room_members viewer
  join public.chat_room_members participant
    on participant.room_id = viewer.room_id
   and participant.user_id = new.id
   and participant.user_id <> viewer.user_id
  join public.chat_rooms cr
    on cr.id = viewer.room_id
  where cr.type = 'direct'
    and cs.conversation_id = viewer.room_id
    and cs.user_id = viewer.user_id;

  return new;
end;
$$;

-- ============================================================
-- PUBLICATION
-- ============================================================
alter publication supabase_realtime add table public.messages;
alter publication supabase_realtime add table public.conversation_summaries
-- ============================================================
-- TRIGGERS
-- ============================================================
drop trigger if exists profiles_set_updated_at on public.profiles;
create trigger profiles_set_updated_at
before update on public.profiles
for each row
execute function public.set_profile_updated_at();

drop trigger if exists auth_user_created_profile on auth.users;
create trigger auth_user_created_profile
after insert on auth.users
for each row
execute function public.handle_auth_user_created_profile();

drop trigger if exists messages_after_insert_update_summaries on public.messages;
create trigger messages_after_insert_update_summaries
after insert on public.messages
for each row
execute function public.handle_message_insert_update_summaries();

drop trigger if exists chat_room_members_after_insert on public.chat_room_members;
create trigger chat_room_members_after_insert
after insert on public.chat_room_members
for each row
execute function public.handle_chat_room_member_insert();

drop trigger if exists chat_room_members_after_delete on public.chat_room_members;
create trigger chat_room_members_after_delete
after delete on public.chat_room_members
for each row
execute function public.handle_chat_room_member_delete();

drop trigger if exists chat_rooms_after_update_sync_summaries on public.chat_rooms;
create trigger chat_rooms_after_update_sync_summaries
after update on public.chat_rooms
for each row
execute function public.handle_chat_room_update_sync_summaries();

drop trigger if exists profiles_after_change_sync_conversation_summaries on public.profiles;
create trigger profiles_after_change_sync_conversation_summaries
after insert or update on public.profiles
for each row
execute function public.handle_profile_update_sync_conversation_summaries();

drop trigger if exists users_after_change_sync_conversation_summaries on public.users;
create trigger users_after_change_sync_conversation_summaries
after insert or update of whatsapp_number on public.users
for each row
execute function public.handle_user_update_sync_conversation_summaries();

-- ============================================================
-- SEED: backfill profiles for existing auth users
-- ============================================================
insert into public.profiles (id, name, photo_url)
select
  u.id,
  coalesce(
    u.raw_user_meta_data ->> 'full_name',
    u.raw_user_meta_data ->> 'name',
    split_part(coalesce(u.email, ''), '@', 1),
    'ConnectX Member'
  ),
  u.raw_user_meta_data ->> 'avatar_url'
from auth.users u
on conflict (id) do upda  te
set
  name      = coalesce(public.profiles.name, excluded.name),
  photo_url = coalesce(public.profiles.photo_url, excluded.photo_url);
```

## Lifecycle

### 1\. Match happens

Backend receives or determines a successful match.

### 2\. Backend creates room and participants

Backend:

* ensures one direct room per pair
* creates a `chat_rooms` row
* inserts two rows into `chat_room_members`

### 3\. DB creates or refreshes summaries

On membership insert, DB trigger should create or refresh one `conversation_summaries` row per member.

For direct chat, each summary row should contain the **other** participant’s metadata from `public.users`.

### 4\. Client chat operations

Client:

* fetches `conversation_summaries`
* fetches `messages` for the active room
* inserts messages through backend into `messages`

### 5\. Message insert side effects

On new message insert, DB trigger should update all summaries for that room:

* `last_message_id = new.id`
* `last_message_text = preview(new)`
* `last_message_at = new.created_at`
* sender summary `unread_count = 0`
* recipient summary `unread_count += 1`
* `updated_at = new.created_at`

### 6\. Read flow

Client calls an RPC such as `mark_conversation_read(conversation_uuid)`.

RPC should:

* verify caller is a room member
* set `unread_count = 0`
* set `last_read_message_id` to the latest room message
* set `last_read_at = now()`

## API / ownership notes for backend

The backend handoff should assume:

* backend endpoint or service handles room creation on match
* backend api for sending message
* frontend can query Supabase directly for inbox and chat history

Expected effect:

* create or reuse the direct room
* ensure both participants exist in `chat_room_members`
* return room id if needed

## Acceptance criteria

* Matching two users creates or reuses exactly one direct chat room
* Both matched users appear in `chat_room_members`
* Summary rows are created automatically for both users
* Client can fetch inbox from `conversation_summaries`
* Client can fetch paginated messages from `messages`
* Client can insert messages directly into `messages`
* Message inserts update summary preview and unread counts automatically
* Opening a conversation clears unread count through RPC
* RLS prevents non-members from reading or inserting messages
* Realtime updates inbox and active-room message list correctly

## Open implementation tasks

* define exact idempotency strategy for one direct room per pair
* implement/verify RLS policies for rooms, membership, messages, and summaries
* implement trigger functions for summary upsert and message side effects
* implement read RPC
* decide whether direct room is created immediately on match or lazily on first chat-open event

## Notes

This issue is a decision and handoff for backend architecture, not just a brainstorm. The explicit decision is:

**Backend owns room creation and participant/message insertion. Client owns message read/fetch through Supabase.**

## Metadata
- URL: [https://linear.app/summondev/issue/CON-62/backend-handoff-direct-chat-room-creation-owned-by-backend-messaging](https://linear.app/summondev/issue/CON-62/backend-handoff-direct-chat-room-creation-owned-by-backend-messaging)
- Identifier: CON-62
- Status: Backlog
- Priority: High
- Assignee: Dimas Oktavian Prasetyo
- Labels: API Contract, Backend, Frontend
- Created: 2026-04-13T03:36:32.839Z
- Updated: 2026-04-16T06:41:21.396Z