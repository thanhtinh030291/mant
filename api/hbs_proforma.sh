dir=$(dirname "$0")
/bin/php $dir/hbs_proforma.php >> /var/log/httpd/hbs_proforma.log 2>&1
