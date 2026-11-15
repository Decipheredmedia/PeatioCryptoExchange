# Pusher Channels pushes live order-book / trade updates to browsers.
# New Pusher apps live in a "cluster" (eu, us2, ap1 ...). Setting
# PUSHER_CLUSTER picks the right API host automatically.
cluster = ENV['PUSHER_CLUSTER'].to_s.strip

Pusher.app_id = ENV['PUSHER_APP']
Pusher.key    = ENV['PUSHER_KEY']
Pusher.secret = ENV['PUSHER_SECRET']
Pusher.host   = ENV['PUSHER_HOST'].presence || (cluster.empty? ? 'api.pusherapp.com' : "api-#{cluster}.pusher.com")
Pusher.port   = ENV['PUSHER_PORT'].present? ? ENV['PUSHER_PORT'].to_i : 80
