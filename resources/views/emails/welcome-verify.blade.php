<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to KoriePay</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">

    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 40px 20px;">
        <tr>
            <td align="center">
                
                <table width="100%" border="0" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                    
                    <tr>
                        <td style="background: linear-gradient(135deg, #158987, #29B475); padding: 40px 30px; text-align: center;">
                            <div style="display: inline-block; width: 48px; height: 48px; background-color: rgba(255,255,255,0.2); border-radius: 12px; line-height: 48px; color: #ffffff; font-size: 24px; font-weight: 900; font-style: italic; margin-bottom: 12px;">
                                K
                            </div>
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 900; letter-spacing: -0.5px;">KoriePay</h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 40px 40px 30px;">
                            <h2 style="margin: 0 0 20px; color: #0f172a; font-size: 20px; font-weight: 800; tracking-tight;">Welcome to the Grid, {{ $name }}.</h2>
                            
                            <p style="margin: 0 0 24px; color: #64748b; font-size: 16px; line-height: 1.6; font-weight: 500;">
                                We are thrilled to have you on board. You've just taken the first step towards seamless cross-border transfers, multi-currency vaults, and secure community wealth building.
                            </p>

                            <p style="margin: 0 0 32px; color: #64748b; font-size: 16px; line-height: 1.6; font-weight: 500;">
                                As a regulated financial institution, we require a quick security check. Please verify your email address to activate your KoriePay vault and begin your KYC onboarding.
                            </p>

                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $url }}" style="display: inline-block; background-color: #020617; color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; padding: 18px 36px; border-radius: 16px; transition: background-color 0.3s;">
                                            Verify Identity
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 40px 40px;">
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; text-align: left;">
                                <p style="margin: 0 0 8px; color: #94a3b8; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Button not working?</p>
                                <p style="margin: 0; color: #64748b; font-size: 12px; line-height: 1.5; word-break: break-all;">
                                    Copy and paste this secure link into your browser:<br>
                                    <a href="{{ $url }}" style="color: #158987; text-decoration: none; font-weight: 600;">{{ $url }}</a>
                                </p>
                            </div>
                        </td>
                    </tr>

                </table>

                <table width="100%" border="0" cellpadding="0" cellspacing="0" style="max-width: 600px; margin-top: 24px;">
                    <tr>
                        <td align="center" style="padding: 0 20px;">
                            <p style="margin: 0 0 12px; color: #94a3b8; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                                Bank-Grade Security
                            </p>
                            <p style="margin: 0 0 20px; color: #94a3b8; font-size: 12px; line-height: 1.5;">
                                KoriePay will never ask for your password or transaction PIN via email. If you did not create an account, please ignore or delete this email.
                            </p>
                            <p style="margin: 0; color: #cbd5e1; font-size: 12px;">
                                &copy; {{ date('Y') }} KoriePay Inc. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>
</html>