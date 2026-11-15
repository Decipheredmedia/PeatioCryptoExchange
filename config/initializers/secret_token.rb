# The secret key signs every login session cookie. It used to be hard-coded
# here (and therefore public on GitHub), which let anyone forge a session.
# It now comes from the SECRET_KEY_BASE environment variable.
secret = ENV['SECRET_KEY_BASE'].to_s

if secret.length < 64
  if Rails.env.production?
    raise 'SECRET_KEY_BASE is missing or too short. Generate one with `openssl rand -hex 64` and put it in your .env file.'
  else
    # Development/test only: a throw-away key that changes on every boot.
    require 'securerandom'
    secret = SecureRandom.hex(64)
  end
end

Peatio::Application.config.secret_key_base = secret
