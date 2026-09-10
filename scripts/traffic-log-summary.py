#!/usr/bin/env python3
"""Read nginx combined logs; output aggregates only, never IPs or query strings.

Usage: python3 scripts/traffic-log-summary.py /var/log/nginx/site-access.log*
Does not alter logs, block visitors, or contact any external service.
"""
import collections
import gzip
import json
import re
import sys
from urllib.parse import urlsplit

pattern = re.compile(r'^(\S+) .*?\[([^]]+)\] "(\S+) (.*?) HTTP/[^" ]+" (\d+) \S+ "([^"]*)" "([^"]*)"')
days, pages, agents, statuses, clients = [collections.Counter() for _ in range(5)]
auth_pages, auth_agents, auth_clients = [collections.Counter() for _ in range(3)]
total = auth_total = 0
for path in sys.argv[1:]:
    opener = gzip.open if path.endswith('.gz') else open
    with opener(path, 'rt', errors='replace') as source:
        for line in source:
            match = pattern.match(line)
            if not match:
                continue
            ip, date, method, uri, status, referrer, agent = match.groups()
            # Exclude common static assets; static assets otherwise dominate counts.
            page = urlsplit(uri).path
            if re.search(r'\.(js|css|png|jpe?g|webp|ico|svg|woff2?)(?:$|/)', page, re.I):
                continue
            total += 1
            days[date.split(':')[0]] += 1
            pages[page] += 1
            agents[agent] += 1
            statuses[status] += 1
            clients[ip] += 1
            if page.startswith('/auth/') or page.rstrip('/') in ['/login', '/es/iniciar-sesion', '/advertise']:
                auth_total += 1
                auth_pages[page] += 1
                auth_agents[agent] += 1
                auth_clients[ip] += 1
print(json.dumps({'non_asset_requests':total, 'dates':dict(days), 'statuses':dict(statuses),
    'top_paths':pages.most_common(12), 'top_user_agents':agents.most_common(5),
    'auth_and_advertise':{'requests':auth_total,'paths':auth_pages.most_common(8),
    'user_agents':auth_agents.most_common(6),'top_client_request_counts':sorted(auth_clients.values(), reverse=True)[:10]},
    'unique_clients':len(clients)}, ensure_ascii=False, indent=2))
