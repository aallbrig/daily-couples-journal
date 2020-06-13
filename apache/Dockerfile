FROM httpd:2.4.33-alpine
RUN apk update; \
    apk upgrade;

COPY dailycouplesjournal.apache.conf /usr/local/apache2/conf/dailycouplesjournal.apache.conf

RUN echo "Include /usr/local/apache2/conf/dailycouplesjournal.apache.conf" \
    >> /usr/local/apache2/conf/httpd.conf

