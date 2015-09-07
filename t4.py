import os, sys, datetime
import ConfigParser

def now():
	return datetime.datetime.now()

print sys.path[0]

testing = "AAA BBB CCC"


pwd = os.path.dirname(os.path.realpath(__file__))
ini = os.path.join(pwd,'terrasens.ini')

Config = ConfigParser.ConfigParser()
Config.read(ini)


print Config.get('temperature', 'room_max')



