#!/usr/bin/env python3

from threading import Thread
import redis
import socket


def name_to_host(hostname, r):
    if not hostname:
        return

    try:
        host = socket.gethostbyname(hostname)
    except socket.gaierror:
        host = '0.0.0.0'

    r.lpush('results', (hostname + ':' + host))


def main():
    r = redis.Redis(host='localhost', port=6379, db=0)

    while True:
        # (b'domain', b'qq.com')
        # (b'domain', b'abc.com')
        bytes_domain = r.blpop('domains')
        domain = ''

        try:
            domain = bytes_domain[1].decode('utf-8')
            t = Thread(target=name_to_host, args=(domain, r), daemon=True)
            t.start()
        except UnicodeDecodeError:
            print('bytes类型数据无法转换为域名: %s' % domain)

if __name__ == '__main__':
    main()
