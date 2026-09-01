<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background-color: #0f172a; color: #f8fafc; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background-color: #020617; border: 2px solid #dc2626; border-radius: 8px; overflow: hidden; }
        .header { background-color: #dc2626; color: #ffffff; padding: 20px; text-align: center; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }
        .content { padding: 30px; }
        .data-grid { background-color: #0f172a; border: 1px solid #334155; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .data-row { display: flex; justify-content: space-between; border-bottom: 1px solid #1e293b; padding: 8px 0; }
        .data-label { color: #94a3b8; font-size: 12px; text-transform: uppercase; }
        .data-value { color: #f8fafc; font-weight: bold; }
        .value-critical { color: #f87171; }
        .footer { padding: 20px; text-align: center; font-size: 10px; color: #64748b; border-top: 1px solid #1e293b; }
        .btn { display: inline-block; background-color: #dc2626; color: #ffffff; padding: 12px 24px; text-decoration: none; font-weight: bold; border-radius: 4px; margin-top: 20px; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            ⚠️ SYSTEM HALTED: Circuit Breaker Tripped
        </div>
        <div class="content">
            <p><strong>ATTN: SUPER ADMIN</strong></p>
            <p>The automated safety protocol has engaged. All cross-border liquidity nodes have been severed due to severe margin degradation.</p>
            
            <div class="data-grid">
                <div class="data-row">
                    <span class="data-label">Event Timestamp</span>
                    <span class="data-value">{{ $timestamp }}</span>
                </div>
                <div class="data-row">
                    <span class="data-label">Failure Margin</span>
                    <span class="data-value value-critical">{{ number_format($margin, 2) }}%</span>
                </div>
                <div class="data-row">
                    <span class="data-label">24H Volume at Halt</span>
                    <span class="data-value">{{ number_format($volume, 2) }}</span>
                </div>
                <div class="data-row">
                    <span class="data-label">Threshold Limit</span>
                    <span class="data-value">1.50%</span>
                </div>
            </div>

            <p style="color: #94a3b8; font-size: 12px;">Immediate action is required. Review the FX Rate Engine and adjust the base multipliers before manually overriding the system halt.</p>
            
            <center>
                <a href="{{ url('/login') }}" class="btn">Access Control Tower</a>
            </center>
        </div>
        <div class="footer">
            SahelPay Infrastructure // Automated Non-Repudiation Alert<br>
            Do not reply to this transmission.
        </div>
    </div>
</body>
</html>