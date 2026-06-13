<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f7fafc;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:600px;max-width:600px;background:#ffffff;border-radius:12px;box-shadow:0 2px 8px rgba(16,24,40,.06);overflow:hidden;font-family:Arial,Helvetica,sans-serif;">
                <tr>
                    <td style="padding:20px 24px;background:#0f172a;color:#ffffff;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="vertical-align:middle;">
                                    @if ($logoUrl)
                                    <img src="{{ $logoUrl }}" alt="{{ $companyName }}" style="height:40px;max-height:40px;display:block;">
                                    @else
                                    <span style="font-size:18px;font-weight:bold;color:#ffffff;">{{ $companyName }}</span>
                                    @endif
                                </td>
                                <td align="right" style="font-size:14px;opacity:.9;">
                                    {{ $companyName }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px 24px;">
                        <h2 style="margin:0 0 8px 0;font-size:22px;line-height:28px;color:#0f172a;">Bienvenido a {{ $companyName }}</h2>
                        <p style="margin:0 0 12px 0;font-size:14px;line-height:20px;color:#334155;">
                            Tu suscripcion ha sido creada exitosamente. A continuacion encontraras las credenciales para acceder al sistema.
                        </p>

                        <div style="margin:16px 0;padding:20px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="font-size:13px;line-height:20px;color:#64748b;padding:6px 0;">URL de acceso:</td>
                                </tr>
                                <tr>
                                    <td style="font-size:15px;line-height:22px;font-weight:600;color:#0f172a;padding:0 0 12px 0;">
                                        <a href="{{ $loginUrl }}" style="color:#2563eb;text-decoration:none;">{{ $loginUrl }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size:13px;line-height:20px;color:#64748b;padding:6px 0;">Usuario:</td>
                                </tr>
                                <tr>
                                    <td style="font-size:15px;line-height:22px;font-weight:600;color:#0f172a;padding:0 0 12px 0;">
                                        {{ $adminEmail }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size:13px;line-height:20px;color:#64748b;padding:6px 0;">Contrasena:</td>
                                </tr>
                                <tr>
                                    <td style="font-size:15px;line-height:22px;font-weight:600;color:#0f172a;padding:0 0 12px 0;letter-spacing:1px;">
                                        {{ $password }}
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <p style="margin:0 0 12px 0;font-size:13px;line-height:18px;color:#334155;">
                            Te recomendamos cambiar la contrasena despues de tu primer inicio de sesion.
                        </p>
                        <p style="margin:0;font-size:12px;line-height:18px;color:#94a3b8;">
                            Si no solicitaste esta suscripcion, ignora este correo.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 24px;background:#f1f5f9;color:#475569;font-size:12px;">
                        &copy; {{ date('Y') }} {{ $companyName }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
