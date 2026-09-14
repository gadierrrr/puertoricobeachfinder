#!/usr/bin/env python3
"""Install targeted Nginx protection on beach-prod; preserve TLS/routes and roll back on failure."""
from pathlib import Path
import re, subprocess, shutil, datetime, os
if os.geteuid() != 0:
 raise SystemExit('Run as root on the production server.')
source=Path(__file__).resolve().parent.parent/'deploy/nginx'
site=Path('/etc/nginx/sites-enabled/puertoricobeachfinder.conf')
http=Path('/etc/nginx/conf.d/beach-traffic-protection.conf')
server=Path('/etc/nginx/snippets/beach-traffic-protection.conf')
geo=Path('/etc/nginx/cloudflare-geo-ranges.conf')
backup=Path('/etc/nginx/backups/traffic-'+datetime.datetime.now(datetime.timezone.utc).strftime('%Y%m%dT%H%M%SZ'))
backup.mkdir(parents=True)
paths=[site,http,server,geo]
existed={p:p.exists() for p in paths}
for i,p in enumerate(paths):
 if p.exists(): shutil.copy2(p,backup/str(i))
text=site.read_text()
anchor='    access_log /var/log/nginx/puertoricobeachfinder-access.log;'
include='    include /etc/nginx/snippets/beach-traffic-protection.conf;'
assert text.count(anchor)==1
ranges=re.findall(r'^set_real_ip_from ([^;]+);$',Path('/etc/nginx/cloudflare-real-ip.conf').read_text(),re.M)
assert len(ranges)>=15
try:
 geo.write_text('\n'.join(x+' 1;' for x in ranges)+'\n')
 shutil.copyfile(source/'traffic-protection-http.conf',http)
 shutil.copyfile(source/'traffic-protection-server.conf',server)
 if include not in text: site.write_text(text.replace(anchor,anchor+'\n'+include))
 subprocess.run(['nginx','-t'],check=True)
 subprocess.run(['systemctl','reload','nginx'],check=True)
except Exception:
 for i,p in enumerate(paths):
  if existed[p]: shutil.copy2(backup/str(i),p)
  elif p.exists(): p.unlink()
 subprocess.run(['nginx','-t'],check=True)
 subprocess.run(['systemctl','reload','nginx'],check=True)
 raise
print('Installed; rollback backup:',backup)
