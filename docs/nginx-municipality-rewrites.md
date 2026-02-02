# Nginx Configuration for Municipality Landing Pages

## Required Rewrite Rules

Add these rules to your Nginx server block configuration to enable clean URLs for municipality landing pages:

```nginx
# Municipality landing pages
# Converts /beaches-in-san-juan to /municipality.php?m=san-juan
location ~ ^/beaches-in-([a-z-]+)$ {
    rewrite ^/beaches-in-([a-z-]+)$ /municipality.php?m=$1 last;
}
```

## Full Example Server Block

```nginx
server {
    listen 80;
    server_name www.puertoricobeachfinder.com puertoricobeachfinder.com;
    root /var/www/beach-finder;
    index index.php index.html;

    # Municipality landing pages
    location ~ ^/beaches-in-([a-z-]+)$ {
        rewrite ^/beaches-in-([a-z-]+)$ /municipality.php?m=$1 last;
    }

    # Beach detail pages (existing)
    location ~ ^/beach/([a-z0-9-]+)$ {
        rewrite ^/beach/([a-z0-9-]+)$ /beach.php?slug=$1 last;
    }

    # PHP handling
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Static files
    location ~* \.(jpg|jpeg|png|gif|webp|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

## Testing

After adding the rewrite rule:

1. **Reload Nginx:**
   ```bash
   sudo nginx -t
   sudo systemctl reload nginx
   ```

2. **Test the URL:**
   ```bash
   curl -I https://www.puertoricobeachfinder.com/beaches-in-san-juan
   ```

   Should return 200 OK

3. **Verify in browser:**
   - Visit: https://www.puertoricobeachfinder.com/beaches-in-san-juan
   - Should show San Juan beach listing page

## Municipality Slug Format

Municipality names are converted to lowercase with hyphens:
- "San Juan" → `san-juan`
- "Cabo Rojo" → `cabo-rojo`
- "Río Grande" → `rio-grande`

## All Available Municipality URLs

Based on your database, these URLs should work:

- /beaches-in-aguada
- /beaches-in-aguadilla
- /beaches-in-arecibo
- /beaches-in-arroyo
- /beaches-in-barceloneta
- /beaches-in-cabo-rojo
- /beaches-in-camuy
- /beaches-in-carolina
- /beaches-in-catano
- /beaches-in-ceiba
- /beaches-in-culebra
- /beaches-in-dorado
- /beaches-in-fajardo
- /beaches-in-guanica
- /beaches-in-guayama
- /beaches-in-guayanilla
- /beaches-in-hatillo
- /beaches-in-humacao
- /beaches-in-isabela
- /beaches-in-lajas
- /beaches-in-loiza
- /beaches-in-luquillo
- /beaches-in-manat
- /beaches-in-maunabo
- /beaches-in-mayaguez
- /beaches-in-naguabo
- /beaches-in-patillas
- /beaches-in-penuelas
- /beaches-in-ponce
- /beaches-in-quebradillas
- /beaches-in-rincon
- /beaches-in-rio-grande
- /beaches-in-salinas
- /beaches-in-san-juan
- /beaches-in-santa-isabel
- /beaches-in-toa-baja
- /beaches-in-vega-alta
- /beaches-in-vega-baja
- /beaches-in-vieques
- /beaches-in-yabucoa
