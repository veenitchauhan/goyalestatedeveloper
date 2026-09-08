#!/bin/sh
# Install only this project's local Apache host; preserve existing sites.
set -eu
project=/Users/veenit/Sites/goyalestatedeveloper
main_config=/opt/homebrew/etc/httpd/httpd.conf
site_config=/opt/homebrew/etc/httpd/extra/goyalestatedeveloper.test.conf
httpd=/opt/homebrew/opt/httpd/bin/httpd
backup_dir=$(mktemp -d /private/tmp/goyal-vhost-backup.XXXXXX)
cp -p "$main_config" "$backup_dir/httpd.conf"
cp -p /etc/hosts "$backup_dir/hosts"
if [ -e "$site_config" ]; then cp -p "$site_config" "$backup_dir/site.conf"; fi
rollback() {
    cp -p "$backup_dir/httpd.conf" "$main_config"
    cp -p "$backup_dir/hosts" /etc/hosts
    if [ -e "$backup_dir/site.conf" ]; then
        cp -p "$backup_dir/site.conf" "$site_config"
    else
        rm -f "$site_config"
    fi
}
trap 'rollback' HUP INT TERM
cp "$project/infra/apache/goyalestatedeveloper.test.conf" "$site_config"
if ! /usr/bin/grep -Fqx 'Include /opt/homebrew/etc/httpd/extra/goyalestatedeveloper.test.conf' "$main_config"; then
    printf '\nInclude /opt/homebrew/etc/httpd/extra/goyalestatedeveloper.test.conf\n' >> "$main_config"
fi
if ! /usr/bin/grep -Eq '^127\.0\.0\.1[[:space:]]+goyalestatedeveloper\.test([[:space:]]|$)' /etc/hosts; then
    printf '\n127.0.0.1 goyalestatedeveloper.test\n' >> /etc/hosts
fi
if ! "$httpd" -t; then rollback; exit 1; fi
if ! "$httpd" -k graceful; then rollback; exit 1; fi
/usr/bin/dscacheutil -flushcache
printf 'Installed goyalestatedeveloper.test. Backups: %s\n' "$backup_dir"
