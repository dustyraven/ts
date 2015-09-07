#!/usr/bin/python
import os, sys, datetime
import Adafruit_DHT
import RPi.GPIO as GPIO
import ConfigParser

# HELPERS

def now():
	return datetime.datetime.now()

def getsens( pin ):

    hmdt, temp = Adafruit_DHT.read_retry(Adafruit_DHT.DHT22, pin)

    t = '{0:0.2f}'.format(temp) if temp is not None else -1
    h = '{0:0.2f}'.format(hmdt) if hmdt is not None else -1

    return t, h

# SETUP

ts_start = now()
pwd = os.path.dirname(os.path.realpath(__file__))
ini = os.path.join(pwd,'terrasens.ini')

Config = ConfigParser.ConfigParser()
Config.read(ini)

pin_warm = Config.get('sensors', 'pin_warm')
pin_cold = Config.get('sensors', 'pin_cold')
pin_room = Config.get('sensors', 'pin_room')


# ACTION

GPIO.setmode(GPIO.BCM)

t_warm, h_warm = getsens(pin_warm)
t_cold, h_cold = getsens(pin_cold)
t_room, h_room = getsens(pin_room)

ts_end = now()
ts_diff = '{0:0.3f}'.format((ts_end - ts_start).total_seconds())

ts = ts_start.strftime("%Y%m%d%H%M%S")

print ts, ts_diff, t_cold, h_cold, t_warm, h_warm, t_room, h_room

t_warm = float(t_warm)

h_avg = (float(h_cold) + float(h_warm)) / 2

GPIO.setwarnings(False)

if t_warm > 30:
    GPIO.setup(22, GPIO.IN)
if t_warm < 28:
    GPIO.setup(22, GPIO.OUT)

if h_avg > 60:
    GPIO.setup(25, GPIO.IN)
if h_avg < 55:
    GPIO.setup(25, GPIO.OUT)

sys.exit(0)

