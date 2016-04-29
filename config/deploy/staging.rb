environments = YAML.load_file("#{File.dirname(__dir__)}/environments.yml")

set :stage,     :staging
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
