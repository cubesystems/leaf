set :application, "_APPLICATION_NAME_HERE_"

set :scm, :git
set :repository, "REPOSITORY_URL_HERE_.git"

server "_SSH_USERNAME_HERE_@_SERVER_HOSTNAME_HERE_", :web, :app

default_run_options[:pty] = true

set :deploy_to, "~/app"
set :deploy_via, :remote_cache
set :use_sudo, false
set :branch, fetch(:branch, "master")

namespace :deploy do
  task :start do ; end
  task :stop do ; end
  task :restart do ; end
  task :finalize_update do ; end

  desc "DB migration preview"
  task :migrate_view do
    run "export LEAF_PRODUCTION=1 && php #{current_path}/cli/migrate.php -v -d"
  end

  desc "DB migration"
  task :migrate do
    run "export LEAF_PRODUCTION=1 && php #{current_path}/cli/migrate.php -v"
  end

  desc "Symlink files and cache from shared"
  task :files_and_cache do
    run "ln -nfs #{shared_path}/cache #{current_path}/shared/cache"
    run "ln -nfs #{shared_path}/files #{current_path}/files"
    run "rm -Rf #{current_path}/Capfile"
    run "rm -Rf #{current_path}/config/deploy*"
    run "rm -Rf #{current_path}/.gitignore"
    run "rm -Rf #{current_path}/.git"
    run "rm -Rf #{current_path}/README.md"
    # write current BRANCH to file
    run "echo #{branch} > #{release_path}/BRANCH"
    # write current REVISION to database
    run "bash -l -c 'php #{release_path}/cli/update_revision.php'"
    # update crontab
    run "bash -l -c 'php #{release_path}/cli/update_crontab.php'"
  end

  desc "setup extras"
  task :setup_extras do
    # setup shared folders and config
    run "mkdir -p #{shared_path}/certs"
    run "mkdir -p #{shared_path}/files"
    run "mkdir -p #{shared_path}/cache;"
    run "if [ ! -f #{shared_path}/config.php ]; then touch #{shared_path}/config.php; fi"

    # remove rails directories created by capistrano
    run "if [ -d #{shared_path}/pids ]; then rmdir #{shared_path}/pids; fi"
    run "if [ -d #{shared_path}/system ]; then rmdir #{shared_path}/system; fi"

    # setup LEAF_PRODUCTION env variable to shell and crontab
    run "if (crontab -l | grep -q LEAF_PRODUCTION=1) ;  then echo /dev/null; else { crontab -l; echo \"LEAF_PRODUCTION=1\"; } | crontab -; fi"
    run "if (grep -q LEAF_PRODUCTION=1 ~/.profile) ;  then echo /dev/null; else  echo \"export LEAF_PRODUCTION=1\" >> ~/.profile; fi"
  end

  desc "ping office"
  task :ping do
    rep = repository.gsub("REPOSITORY_URL_HERE_:", "").gsub(".git", "")
    require 'net/http'
    require 'uri'
    if !(exists? :rails_env)
      set :rails_env, "production"
    end

    link = "https://office.cube.lv/services/registerDeployment.php?do=deploy&repository=#{rep}&deployedCommitHash=#{current_revision}&environment=#{rails_env}&deployedBranch=#{branch}"

    uri = URI.parse(link)
    http = Net::HTTP.new(uri.host, uri.port)
    http.open_timeout = 2;
    http.use_ssl = true
    http.verify_mode = OpenSSL::SSL::VERIFY_NONE

    request = Net::HTTP::Get.new(uri.request_uri)

    begin
      response = http.request(request)
      puts 'office response: ' + response.body
    rescue Timeout::Error
      #Connection failed
      puts "office ping is not available - connection timed out"
    end
  end
end

after "deploy", "deploy:files_and_cache", "deploy:cleanup", "deploy:ping"
after "deploy:setup", "deploy:setup_extras"
