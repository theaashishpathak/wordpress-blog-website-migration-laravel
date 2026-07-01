<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm your subscription</title>
</head>
<body style="margin:0; padding:0; background:#f8fafc; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; padding:40px 16px;">
        <tr>
            <td align="center">
                <table width="560" cellpadding="0" cellspacing="0" style="background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(15,23,42,0.06);">
                    <tr>
                        <td style="padding:32px; text-align:center; background:linear-gradient(135deg,#6366f1 0%,#a855f7 50%,#ec4899 100%);">
                            <h1 style="margin:0; color:#fff; font-size:24px; font-weight:800; letter-spacing:-0.5px;">
                                {{ $siteName }}
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 32px 16px;">
                            <h2 style="margin:0 0 12px; color:#0f172a; font-size:22px; font-weight:800;">
                                Almost there!
                            </h2>
                            <p style="margin:0 0 20px; color:#475569; font-size:15px; line-height:1.6;">
                                Thanks for signing up for the <strong>{{ $siteName }}</strong> newsletter.
                                One last step — click the button below to confirm your email address.
                            </p>

                            <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background:#4f46e5; border-radius:10px;">
                                        <a href="{{ $confirmUrl }}"
                                           style="display:inline-block; padding:14px 28px; color:#fff; font-weight:700; font-size:15px; text-decoration:none;">
                                            Confirm subscription
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:24px 0 0; color:#64748b; font-size:13px; line-height:1.6;">
                                If the button doesn't work, copy this link into your browser:<br>
                                <a href="{{ $confirmUrl }}" style="color:#4f46e5; word-break:break-all;">{{ $confirmUrl }}</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px 32px; border-top:1px solid #e2e8f0;">
                            <p style="margin:0; color:#94a3b8; font-size:11px; line-height:1.5;">
                                Didn't sign up? You can ignore this email — we won't add you to the list without confirmation.
                                <br><br>
                                Or
                                <a href="{{ $unsubscribeUrl }}" style="color:#94a3b8; text-decoration:underline;">unsubscribe instantly</a>.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
