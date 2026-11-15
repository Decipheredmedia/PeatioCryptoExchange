# Peatio is a 2014-era Rails 4.0 / Ruby 2.2 app. Those versions can no longer
# be installed on Ubuntu 24.04 (OpenSSL 3, MySQL 8), so we run the app in a
# container that has the old Debian 9 system it was built for.
FROM ruby:2.2-stretch

# Debian 9 ("stretch") has been moved to the archive server.
RUN sed -i -e 's|deb.debian.org|archive.debian.org|g' \
           -e 's|security.debian.org|archive.debian.org|g' \
           -e '/stretch-updates/d' /etc/apt/sources.list \
 && echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99archive \
 && apt-get update \
 && apt-get install -y --no-install-recommends nodejs imagemagick netcat-openbsd \
 && rm -rf /var/lib/apt/lists/*

ENV RAILS_ENV=production \
    RAILS_SERVE_STATIC_FILES=true \
    RAILS_LOG_TO_STDOUT=true \
    BUNDLE_WITHOUT=development:test \
    BUNDLE_JOBS=2 \
    BUNDLE_RETRY=3

WORKDIR /app

RUN gem install bundler -v 1.17.3 --no-document

COPY Gemfile Gemfile.lock ./
RUN bundle _1.17.3_ install

COPY . .
RUN chmod +x docker/*.sh bin/* \
 && mkdir -p log tmp/pids public/uploads

ENTRYPOINT ["/app/docker/entrypoint.sh"]
CMD ["/app/docker/start-web.sh"]
