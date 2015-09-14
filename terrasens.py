#!/usr/bin/python
import os, sys, datetime
import Adafruit_DHT
import RPi.GPIO as GPIO
import ConfigParser
from beebotte import *
# from decimal import *

# HELPERS

def now():
	return datetime.datetime.now()

def getsens( pin ):

    hmdt, temp = Adafruit_DHT.read_retry(Adafruit_DHT.DHT22, pin)

    t = '{0:0.2f}'.format(temp) if temp is not None else -1
    h = '{0:0.2f}'.format(hmdt) if hmdt is not None else -1

    return t, h


def setCtrl(pinNum, state):
    if 0 == state:
        GPIO.setup(pinNum, GPIO.IN)
    elif 1 == state:
        GPIO.setup(pinNum, GPIO.OUT)
    else:
        pass

def getCtrl(pinNum):
    return int(GPIO.gpio_function(pinNum) == GPIO.OUT)


def chkInit(pinNum):
    if GPIO.gpio_function(pinNum) == GPIO.IN:
        GPIO.setup(pinNum, GPIO.OUT)
        GPIO.output(pinNum, False)



# SETUP


ts_start = now()
pwd = os.path.dirname(os.path.realpath(__file__))
ini = os.path.join(pwd,'terrasens.ini')
log = os.path.join(pwd,'logs','terrasens.log')

Config = ConfigParser.ConfigParser()
Config.read(ini)

pin_warm = int(Config.get('sensors', 'pin_warm'))
pin_cold = int(Config.get('sensors', 'pin_cold'))
pin_room = int(Config.get('sensors', 'pin_room'))

target_temp = int(Config.get('temperature', 'target'))
target_hmdt = int(Config.get('humidity', 'target'))

pin_heater = int(Config.get('control', 'pin_heater'))
pin_humidifier = int(Config.get('control', 'pin_humidifier'))
pin_lamp = int(Config.get('control', 'pin_lamp'))

bbt_apikey = Config.get('beebotte', 'api_key')
bbt_secret = Config.get('beebotte', 'secret')
bbt_token  = Config.get('beebotte', 'token')

# for Decimal
#getcontext().prec = 2


# ACTION

GPIO.setmode(GPIO.BCM)
GPIO.setwarnings(False)

for pin in [2,3,4,17,27,22,10,9,11]:
    chkInit(pin)

#sys.exit(0)

t_warm, h_warm = getsens(pin_warm)
t_cold, h_cold = getsens(pin_cold)
t_room, h_room = getsens(pin_room)

h_avg = round((float(h_cold) + float(h_warm)) / 2, 1)
t_avg = round((float(t_cold) + float(t_warm)) / 2, 1)

ts_end = now()
ts_diff = '{0:0.3f}'.format((ts_end - ts_start).total_seconds())

ts = ts_start.strftime("%Y%m%d%H%M%S")

"""
tempW = float(t_warm)

if tempW > 0:
    if tempW > target_temp:
        setCtrl(pin_heater, 0)
    if tempW < target_temp:
        setCtrl(pin_heater, 1)

if h_avg > 0:
    if h_avg > target_hmdt:
        setCtrl(pin_humidifier, 0)
    if h_avg < target_hmdt:
        setCtrl(pin_humidifier, 1)
"""

c_heater = getCtrl(pin_heater)
c_humidifier = getCtrl(pin_humidifier)
c_lamp = getCtrl(pin_lamp)


c_heater = 0
c_humidifier = 0
c_lamp = 0

print ts, ts_diff, t_cold, h_cold, t_warm, h_warm, t_room, h_room, c_heater, c_humidifier, c_lamp

try:

    bbt = BBT(bbt_apikey, bbt_secret)

    bbt.writeBulk('TerraSens', [
        {   'resource': 'h_warm',   'data': round(float(h_warm),1) },
        {   'resource': 'h_cold',   'data': round(float(h_cold),1) },
        {   'resource': 'h_room',   'data': round(float(h_room),1) },
        {   'resource': 'h_avg',    'data': h_avg },

        {   'resource': 't_warm',   'data': round(float(t_warm),1) },
        {   'resource': 't_cold',   'data': round(float(t_cold),1) },
        {   'resource': 't_room',   'data': round(float(t_room),1) },
        {   'resource': 't_avg',    'data': t_avg },

        {   'resource': 'c_heater',     'data': c_heater },
        {   'resource': 'c_humidifier', 'data': c_humidifier },
        {   'resource': 'c_lamp',       'data': c_lamp }
    ])

except:
    pass


sys.exit(0)

