import os, sys, datetime


def now():
	print testing
	return datetime.datetime.now()

print sys.path[0]

testing = "AAA BBB CCC"

print os.path.join(os.path.dirname(os.path.realpath(__file__)),"output","log.txt")

print now()


