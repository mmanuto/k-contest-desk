# set the base href on html root
sed -i 's/<base href="\/">/<base href="\/'$SUBFOLDER'">/g' /usr/share/nginx/html/index.html

# replace environment variable in angular code
find /usr/share/nginx/html -maxdepth 1 -iname 'main.*.js' -type f -exec sh -c "envsubst '\${BACKEND_URL}' < {} > /tmp/tmp.js; mv /tmp/tmp.js {}" \;
find /usr/share/nginx/html -maxdepth 1 -iname 'main.*.js' -type f -exec sh -c "envsubst '\${NEW_BACKEND_URL}' < {} > /tmp/tmp.js; mv /tmp/tmp.js {}" \;
find /usr/share/nginx/html -maxdepth 1 -iname 'main.*.js' -type f -exec sh -c "envsubst '\${WEB_SUBFOLDER}' < {} > /tmp/tmp.js; mv /tmp/tmp.js {}" \;
find /usr/share/nginx/html -maxdepth 1 -iname 'main.*.js' -type f -exec sh -c "envsubst '\${GEST_SUBFOLDER}' < {} > /tmp/tmp.js; mv /tmp/tmp.js {}" \;

find /usr/share/nginx/html -maxdepth 1 -iname 'main.*.js' -type f -exec sh -c "envsubst '\${API_TOKEN}' < {} > /tmp/tmp.js; mv /tmp/tmp.js {}" \;
find /usr/share/nginx/html -maxdepth 1 -iname 'main.*.js' -type f -exec sh -c "envsubst '\${ENABLE_HEARTBEAT}' < {} > /tmp/tmp.js; mv /tmp/tmp.js {}" \;
find /usr/share/nginx/html -maxdepth 1 -iname 'main.*.js' -type f -exec sh -c "envsubst '\${PATH_HEART_BEAT}' < {} > /tmp/tmp.js; mv /tmp/tmp.js {}" \;
find /usr/share/nginx/html -maxdepth 1 -iname 'main.*.js' -type f -exec sh -c "envsubst '\${TENANT}' < {} > /tmp/tmp.js; mv /tmp/tmp.js {}" \;

find /usr/share/nginx/html -maxdepth 1 -iname 'main.*.js' -type f -exec sh -c "envsubst '\${LOCAL_STORAGE__PREFIX}' < {} > /tmp/tmp.js; mv /tmp/tmp.js {}" \;

#base image docker entrypoint
nginx -g 'daemon off;'
