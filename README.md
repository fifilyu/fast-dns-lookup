# fast-dns-lookup

多线程域名解析查询工具

## 适用场景
* 网站服务提供商

需要大批量并且快速的查看域名解析，并且能够显示解析IP是否在自己指定的IP地址范围。

比如，上级单位发送一个域名列表，要求处理列表中使用本公司业务的域名。

## 运行环境
* PHP 7.0及其以上版本
* Redis 2.0及其以上版本
* Python 3.0及其以上版本

## 使用方法
1. `git clone https://github.com/fifilyu/fast-dns-lookup /data/web/fast-dns-lookup`
2. `git submodule init`
3. `cd /data/web/fast-dns-lookup`
4. `pecl install redis`
5. `pip3 install -r requirements.txt`
6. `nohup python3 /data/web/fast-dns-lookup/agent.py &`
7. 在Apache Httpd或者Ningx中配置vhost，并重启Web服务器或重载配置

Apache Httpd配置示例：

    NameVirtualHost *:8080
    
    <VirtualHost *:8080>
        DocumentRoot /data/web/fast-dns-lookup/public
        ServerName localhost
    </VirtualHost>

Nginx配置示例：

    server {
        listen 8080;
        root /data/web/fast-dns-lookup/public;
    
        location ~ \.php$ {
            include  /etc/nginx/fastcgi_params;
            fastcgi_pass   127.0.0.1:9000;
            fastcgi_index  index.php;
        }
    }


最后，即可访问 `http://localhost:8080`


## 目录结构

* ip.txt： 自定义IP地址范围
* agent.py： 多线程域名解析代理
* public： 网站运行目录
* composer.json：composer配置文件
* includes：项目内部依赖文件
* ip.txt.example：自定义IP地址示例
* php-domain-name：外部项目依赖
* README.md：README
* requirements.txt：python3外部依赖配置文件
* tests：单元测试