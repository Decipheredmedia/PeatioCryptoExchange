#!/usr/bin/env ruby
# Builds Peatio's config/*.yml files from the values in your .env file, so you
# never have to edit YAML by hand. Runs automatically every time a Peatio
# container starts. (Kept compatible with Ruby 2.2.)
require 'yaml'
require 'fileutils'

Dir.chdir(File.expand_path('..', File.dirname(__FILE__)))

def env(key, default = nil)
  value = ENV[key]
  value.nil? || value.strip.empty? ? default : value
end

# 1. Copy any example file that has no real file yet
#    (currencies.yml, markets.yml, banks.yml ...). Your edits are never overwritten.
Dir.glob('config/*.yml.example').each do |example|
  target = example.sub(/\.example\z/, '')
  FileUtils.cp(example, target) unless File.exist?(target)
end

# 2. config/application.yml - read by the app through Figaro.
admin_email = env('ADMIN_EMAIL', 'admin@peatio.info')
app = {
  'URL_HOST'            => env('URL_HOST', 'localhost:3000'),
  'URL_SCHEMA'          => env('URL_SCHEMA', 'http'),
  'SESSION_EXPIRE'      => env('SESSION_EXPIRE', '60'),
  'ADMIN'               => env('ADMIN_EMAILS', admin_email),
  'TIMEZONE'            => env('TIMEZONE', 'UTC'),
  'REDIS_URL'           => env('REDIS_URL', 'redis://redis:6379'),
  'SMTP_PORT'           => env('SMTP_PORT', '587'),
  'SMTP_DOMAIN'         => env('SMTP_DOMAIN', 'localhost'),
  'SMTP_ADDRESS'        => env('SMTP_ADDRESS', 'localhost'),
  'SMTP_USERNAME'       => env('SMTP_USERNAME', ''),
  'SMTP_PASSWORD'       => env('SMTP_PASSWORD', ''),
  'SMTP_AUTHENTICATION' => env('SMTP_AUTHENTICATION', 'plain'),
  'SUPPORT_MAIL'        => env('SUPPORT_MAIL', admin_email),
  'SYSTEM_MAIL_FROM'    => env('SYSTEM_MAIL_FROM', admin_email),
  'SYSTEM_MAIL_TO'      => env('SYSTEM_MAIL_TO', admin_email),
  'OPERATE_MAIL_TO'     => env('OPERATE_MAIL_TO', admin_email),
  'WEBSOCKET_HOST'      => '0.0.0.0',
  'WEBSOCKET_PORT'      => '8080',
  'PUSHER_APP'          => env('PUSHER_APP', ''),
  'PUSHER_KEY'          => env('PUSHER_KEY', ''),
  'PUSHER_SECRET'       => env('PUSHER_SECRET', ''),
  'PUSHER_CLUSTER'      => env('PUSHER_CLUSTER', ''),
  'PUSHER_PORT'         => env('PUSHER_PORT', '80'),
  'PUSHER_WS_PORT'      => env('PUSHER_WS_PORT', '80'),
  'PUSHER_WSS_PORT'     => env('PUSHER_WSS_PORT', '443'),
  'PUSHER_ENCRYPTED'    => env('PUSHER_ENCRYPTED', 'true'),
  'TWILIO_NUMBER'       => env('TWILIO_NUMBER', ''),
  'TWILIO_SID'          => env('TWILIO_SID', ''),
  'TWILIO_TOKEN'        => env('TWILIO_TOKEN', '')
}
%w(PUSHER_HOST PUSHER_WS_HOST).each { |k| app[k] = env(k) if env(k) }
app.each { |k, v| app[k] = v.to_s }

File.open('config/application.yml', 'w') do |f|
  f.write("# Generated from .env by docker/write_config.rb - edit .env instead.\n")
  f.write(YAML.dump('production' => app.dup, 'development' => app.dup, 'test' => app.dup))
end

# 3. config/database.yml
db = {
  'adapter'   => 'mysql2',
  'encoding'  => 'utf8',
  'host'      => env('DB_HOST', 'db'),
  'port'      => env('DB_PORT', '3306').to_i,
  'database'  => env('DB_NAME', 'peatio_production'),
  'username'  => env('DB_USER', 'peatio'),
  'password'  => env('DB_PASSWORD', ''),
  'pool'      => 10,
  'reconnect' => true
}
File.open('config/database.yml', 'w') do |f|
  f.write("# Generated from .env by docker/write_config.rb - edit .env instead.\n")
  f.write(YAML.dump('production'  => db,
                    'development' => db.merge('database' => 'peatio_development'),
                    'test'        => db.merge('database' => 'peatio_test')))
end

# 4. config/amqp.yml - only the connection part changes.
amqp = YAML.load_file('config/amqp.yml')
amqp['connect'] = {
  'host'     => env('RABBITMQ_HOST', 'rabbitmq'),
  'port'     => env('RABBITMQ_PORT', '5672').to_i,
  'username' => env('RABBITMQ_USER', 'peatio'),
  'password' => env('RABBITMQ_PASSWORD', '')
}
File.open('config/amqp.yml', 'w') { |f| f.write(YAML.dump(amqp)) }

puts '>> Config files written from .env'
