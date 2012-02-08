require 'mysql2'
require 'date'
require 'json'
require 'yaml'

# This is a super-quick script that exports the contents of your Etherpad Lite MySql store to
# static HTML.  I created this because it was becoming a critical part of our infrastructure and
# thus neeeded some kind of readable backup system.

# load settings
VERSION = 0.1
CONFIG_FILE = "settings.yml"
if !File.exists? CONFIG_FILE
  puts "ERROR: you need to create a settings.yml, based on settings.yml.template"
  exit
end
config = YAML.load(File.open(CONFIG_FILE))
client = Mysql2::Client.new(:host=>config['db']['host'],
                            :username=>config['db']['username'],
                            :password=>config['db']['password'],
                            :database=>config['db']['database'],
                           )

puts "Starting eplite export (v#{VERSION})"

results = client.query("SELECT count(*) as total FROM store")
total = results.first['total']
puts "  Found #{total} rows in the store"

results = client.query("SELECT count(*) as total FROM  `store` WHERE  `key` NOT LIKE  '%:revs:%' AND  `key` LIKE  'pad:%' AND `key` NOT LIKE  '%:chat:%'")
total = results.first['total']
puts "  Found #{total} unique pads in the store"

def start_html_file(file, title)
  file.write "<html>\n"
  file.write "<head>\n"
  file.write '<meta http-equiv="content-type" content="text/html;charset=utf-8"/>'+"\n"
  file.write "<title>#{title} : Etherpad-Lite Export</title>"
  file.write "</head>\n"
  file.write "<body>\n"
end

def end_html_file(file)
  file.write("</html>\n")
  file.write("</body>\n")
end

# setup export dirs
now = Time.now
export_dirname = "eplite-backup"
export_dirname = export_dirname + now.strftime("%Y%m%d-%H%M%S") if config['timestamp']
export_path = File.join(config['backup_dir'], export_dirname)
Dir.mkdir(export_path) if !File.exists?(export_path)
pad_export_path = File.join(export_path,"pads")
Dir.mkdir(pad_export_path) if !File.exists?(pad_export_path)
puts "  Exporting to #{export_path}"

# start the toc
index = File.open(File.join(export_path,"index.html"),'w')
start_html_file(index, "Table Of Contents")
index.write("<h1>Table of Contents</h1>")
index.write("<ul>\n")
server_index = File.open(File.join(export_path,"server-toc.html"),'w')
start_html_file(server_index, "Table Of Contents")
server_index.write("<h1>Table of Contents</h1>")
server_index.write("<ul>\n")

# go through all the pad master entries, saving the content of each
results = client.query("SELECT * FROM  `store` WHERE  `key` NOT LIKE  '%:revs:%' AND  `key` LIKE  'pad:%' AND `key` NOT LIKE  '%:chat:%' ORDER BY `key`")
results.each do |pad|
  title = pad['key'].sub("pad:","")
  pad_value = JSON.parse(pad['value'])
  contents = pad_value['atext']['text']
  # http://stackoverflow.com/questions/1268289/how-to-get-rid-of-non-ascii-characters-in-ruby
  filename = title.gsub(/[\u0080-\u00ff]/,"")+".html"
  # add an item to the table of contents
  index.write("  <li><a href=\"pads/#{filename}\">#{title}</a></li>\n")
  server_index.write("  <li><a href=\"#{config['base_url']}p/#{title}\">#{title}</a></li>\n")
  # export the contents too
  pad_file = File.open(File.join(pad_export_path,filename),'w')
  start_html_file(pad_file, title)
  pad_file.write("<pre>\n")
  pad_file.write("#{contents}\n")
  pad_file.write("</pre>\n")
  end_html_file(pad_file)
  pad_file.close
end

index.write "</ul>\n"
end_html_file(index)
server_index.write "</ul>\n"
end_html_file(server_index)
index.close
server_index.close

puts "Done"