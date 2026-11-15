# The admin login used to be hard-coded (admin@peatio.info / algobasket),
# which is public on GitHub. It now comes from your .env file.
ADMIN_EMAIL    = ENV['ADMIN_EMAIL'].presence || 'admin@peatio.info'
ADMIN_PASSWORD = ENV['ADMIN_PASSWORD'].to_s

if ADMIN_PASSWORD.length < 12 || ADMIN_PASSWORD == 'algobasket'
  raise 'Set ADMIN_PASSWORD (at least 12 characters) in your .env file before seeding.'
end

admin_identity = Identity.find_or_create_by(email: ADMIN_EMAIL)
admin_identity.password = admin_identity.password_confirmation = ADMIN_PASSWORD
admin_identity.is_active = true
admin_identity.save!

admin_member = Member.find_or_create_by(email: ADMIN_EMAIL)
admin_member.authentications.build(provider: 'identity', uid: admin_identity.id)
admin_member.save!

if Rails.env == 'development'
  NORMAL_PASSWORD = 'development-only-password'

  foo = Identity.create(email: 'foo@peatio.info', password: NORMAL_PASSWORD, password_confirmation: NORMAL_PASSWORD, is_active: true)
  foo_member = Member.create(email: foo.email)
  foo_member.authentications.build(provider: 'identity', uid: foo.id)
  foo_member.tag_list.add 'vip'
  foo_member.tag_list.add 'hero'
  foo_member.save

  bar = Identity.create(email: 'bar@peatio.info', password: NORMAL_PASSWORD, password_confirmation: NORMAL_PASSWORD, is_active: true)
  bar_member = Member.create(email: bar.email)
  bar_member.authentications.build(provider: 'identity', uid: bar.id)
  bar_member.tag_list.add 'vip'
  bar_member.tag_list.add 'hero'
  bar_member.save
end
