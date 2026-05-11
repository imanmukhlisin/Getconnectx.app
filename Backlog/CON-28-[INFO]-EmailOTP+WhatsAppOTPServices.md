# [BE] Email OTP + WhatsApp OTP services

Email OTP:

* POST /auth/send-otp (generate 6-digit code, send via email)
* POST /auth/verify-otp
* Resend with cooldown timer

WhatsApp OTP:

* POST /auth/send-wa-otp (send via WhatsApp provider)
* POST /auth/verify-wa-otp
* Integration with Twilio/Fonnte or similar

## Metadata
- URL: [https://linear.app/summondev/issue/CON-28/be-email-otp-whatsapp-otp-services](https://linear.app/summondev/issue/CON-28/be-email-otp-whatsapp-otp-services)
- Identifier: CON-28
- Status: In Review
- Priority: High
- Assignee: Unassigned
- Labels: Backend
- Project: [ConnectX App](https://linear.app/summondev/project/connectx-app-675430b67153/overview). Mobile-first swipe-based matching platform for startup founders, co-founders, and team members
- Project milestone: 1 - Auth Ready (target: 2026-04-08T17:00:00.000Z)
- Due date: 2026-04-07T17:00:00.000Z
- Created: 2026-04-04T09:45:09.745Z
- Updated: 2026-04-10T17:19:31.871Z