<?php
declare(strict_types=1);

// Basit parola koruması (HTTPS değildir).
// Trafiği gerçekten "şifrelemek" için HTTPS (Caddy/Nginx/Apache) gerekir.
//
// ÖNEMLİ: Parolayı değiştir.
const KC2_AUTH_PASSWORD = 'serveur';

// İstersen "logout" linkiyle çıkış yapılabilir.
// Tek kullanıcı senaryosu için kullanıcı adı kullanılmıyor.

