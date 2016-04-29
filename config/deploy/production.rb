require 'socket'
require 'net/ssh/proxy/command'

environments = YAML.load_file("#{File.dirname(__dir__)}/environments.yml")

set :stage,     :production
set :user,      environments["#{fetch(:stage)}_user"]
set :group,     environments["#{fetch(:stage)}_user"]
set :app_url,   'http://' + environments["#{fetch(:stage)}_uri"]
set :deploy_to, environments["#{fetch(:stage)}_deploy_path"]

host = fetch(:user) + '@' + environments["#{fetch(:stage)}_host"]
role :app, [host]
role :web, [host]
role :db,  [host]

set :ssh_options, {
  forward_agent: true
}

if Socket.gethostname != 'minasanor'
  set :ssh_options, fetch(:ssh_options).merge({
    proxy: Net::SSH::Proxy::Command.new('ssh deploy@minasanor.genero.fi nc %h %p 2> /dev/null')
  })
end
