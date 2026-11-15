# Refuse to boot with the placeholder Pusher values from application.yml.example.
missing = %w(PUSHER_APP PUSHER_KEY PUSHER_SECRET).select do |key|
  ENV[key].to_s.strip.empty? || ENV[key] =~ /^YOUR/
end

unless missing.empty?
  puts "====================== WARNING ======================"
  puts "  please set these values in your .env file:"
  puts ""
  missing.each { |key| puts "  #{key}" }
  puts "====================================================="
  raise "config missing" unless ENV['ALLOW_MISSING_PUSHER'] == 'true'
end
