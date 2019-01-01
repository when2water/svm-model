FROM php:5-apache

RUN apt-get update && apt-get install -y \
  apt-utils \
  git \
  python3 \
  python3-tk \
  python3-dev \
  python3-pip

RUN pip3 install scikit-learn numpy scipy matplotlib

ENV APACHE_RUN_USER=#1000

COPY php.ini /usr/local/etc/php/
COPY . /var/www/html
