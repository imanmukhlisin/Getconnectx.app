<x-mail::message>
# You're Invited to Join a Startup Team!

You have been invited to join **{{ $startup->name }}** on ConnectX.

**Role:** {{ ucwords(str_replace('_', ' ', $invitation->role_id)) }}
**Commitment:** {{ ucwords(str_replace('_', ' ', $invitation->commitment)) }}
**Equity Offered:** {{ $invitation->equity_percent }}%

<x-mail::button :url="config('app.frontend_url') . '/invitations'">
Review Invitation
</x-mail::button>

If you don't have an account yet, you can sign up using this email address to view the invitation details.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
