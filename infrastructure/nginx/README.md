# Nginx 設定

## TLS 1.2+ 設定要件

本番環境では TLS 1.2 以上を必須とし、セキュリティ標準に準拠した暗号化通信を行う。

### 必須設定

```nginx
# TLS 1.2 以上のみ許可（1.0, 1.1 は無効化）
ssl_protocols TLSv1.2 TLSv1.3;

# 安全な暗号スイートのみ使用
ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384';
ssl_prefer_server_ciphers on;

# HSTS（HTTP Strict Transport Security）
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

# セッション設定
ssl_session_cache shared:SSL:10m;
ssl_session_timeout 10m;

# OCSP Stapling
ssl_stapling on;
ssl_stapling_verify on;
resolver 8.8.8.8 8.8.4.4 valid=300s;
resolver_timeout 5s;
```

### セキュリティヘッダー

```nginx
# XSS 保護
add_header X-XSS-Protection "1; mode=block" always;

# コンテンツタイプスニッフィング防止
add_header X-Content-Type-Options "nosniff" always;

# クリックジャッキング防止
add_header X-Frame-Options "SAMEORIGIN" always;

# リファラーポリシー
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
```

### 設定確認方法

TLS 設定は以下のツールで検証できる：

1. **SSL Labs**: https://www.ssllabs.com/ssltest/
   - 目標: A+ 評価

2. **testssl.sh**:
   ```bash
   ./testssl.sh https://your-domain.com
   ```

3. **nmap**:
   ```bash
   nmap --script ssl-enum-ciphers -p 443 your-domain.com
   ```

## 参考資料

- [00_docs/20_tech/99_standard/security/04_EncryptionPolicy.md](../../00_docs/20_tech/99_standard/security/04_EncryptionPolicy.md)
- [Mozilla SSL Configuration Generator](https://ssl-config.mozilla.org/)
- [OWASP TLS Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Transport_Layer_Security_Cheat_Sheet.html)

@feature 001-security-preparation
