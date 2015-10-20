#!/usr/bin/python
import os, sys, time, datetime, warnings
import Adafruit_DHT
import RPi.GPIO as GPIO
import ConfigParser
from beebotte import *
# from decimal import *

# HELPERS

def now():
	return datetime.datetime.now()

def getsens( pin ):

    #def read_retry(sensor, pin, retries=15, delay_seconds=2, platform=None)
    hmdt, temp = Adafruit_DHT.read_retry(Adafruit_DHT.DHT22, pin, 10, 1, platform)

    t = '{0:0.2f}'.format(temp) if temp is not None else -1
    h = '{0:0.2f}'.format(hmdt) if hmdt is not None else -1

    return t, h


def setCtrl(pinNum, state):
    GPIO.output(pinNum, state)
    """
    if 0 == state:
        GPIO.setup(pinNum, GPIO.IN)
    elif 1 == state:
        GPIO.setup(pinNum, GPIO.OUT)
    else:
        pass
    """

def getCtrl(pinNum):
    return int(GPIO.input(pinNum))
    #return int(GPIO.gpio_function(pinNum) == GPIO.OUT)

def setOut(pinNum):
    with warnings.catch_warnings():
        warnings.simplefilter("ignore")
        GPIO.setup(pinNum, GPIO.OUT)

def setIn(pinNum):
    with warnings.catch_warnings():
        warnings.simplefilter("ignore")
        GPIO.setup(pinNum, GPIO.IN)

def chkInit(pinNum):
    if GPIO.gpio_function(pinNum) == GPIO.IN:
        setOut(pinNum)
        GPIO.output(pinNum, False)


def buzz(sec):
    if 0 == sec:
        return 0
    setCtrl(pin_buzz, 1)
    time.sleep(sec)
    setCtrl(pin_buzz, 0)


# SETUP


ts_start = now()
pwd = os.path.dirname(os.path.realpath(__file__))
ini = os.path.join(pwd,'terrasens.ini')
log = os.path.join(pwd,'logs','terrasens.log')

Config = ConfigParser.ConfigParser()
Config.read(ini)

platform = Adafruit_DHT.common.get_platform() #Adafruit_DHT.platform_detect.platform_detect()

pin_warm = int(Config.get('sensors', 'pin_warm'))
pin_cold = int(Config.get('sensors', 'pin_cold'))
pin_room = int(Config.get('sensors', 'pin_room'))

cold_min_temp = int(Config.get('temperature', 'cold_min'))
target_temp = int(Config.get('temperature', 'target'))
target_hmdt = int(Config.get('humidity', 'target'))
lamp_freq = int(Config.get('temperature', 'lamp_freq'))

pin_heater = int(Config.get('control', 'pin_heater'))
pin_humidifier = int(Config.get('control', 'pin_humidifier'))
pin_lamp = int(Config.get('control', 'pin_lamp'))
pin_warn = int(Config.get('control', 'pin_warn'))
pin_ctrl_5 = int(Config.get('control', 'pin_ctrl_5'))
pin_ctrl_6 = int(Config.get('control', 'pin_ctrl_6'))
pin_ctrl_7 = int(Config.get('control', 'pin_ctrl_7'))
pin_buzz = int(Config.get('control', 'pin_buzz'))

bbt_apikey = Config.get('beebotte', 'api_key')
bbt_secret = Config.get('beebotte', 'secret')
bbt_token  = Config.get('beebotte', 'token')

buzz_time = 0

# for Decimal
#getcontext().prec = 2


# ACTION

GPIO.setmode(GPIO.BCM)
GPIO.setwarnings(False)

for pin in [pin_heater,pin_humidifier,pin_lamp,pin_warn,pin_ctrl_5,pin_ctrl_6,pin_ctrl_7,pin_buzz]:
    chkInit(pin)

for pin in [pin_warm, pin_cold, pin_room]:
    setIn(pin)

for pin in [pin_heater, pin_humidifier, pin_lamp, pin_buzz, pin_warn]:
    setOut(pin)


#sys.exit(0)

t_warm, h_warm = getsens(pin_warm)
t_cold, h_cold = getsens(pin_cold)
t_room, h_room = getsens(pin_room)

tempW = float(t_warm)
tempC = float(t_cold)
hmdtW = float(h_warm)
hmdtC = float(h_cold)


if hmdtC > 0 and hmdtW > 0:
    h_avg = round((hmdtC + hmdtW) / 2, 1)
else:
    h_avg = -1
    buzz_time += 1

if tempC > 0 and tempW > 0:
    t_avg = round((tempC + tempW) / 2, 1)
else:
    t_avg = -1
    buzz_time += 1

#h_avg = round((hmdtC + hmdtW) / 2, 1) if hmdtC > 0 and hmdtW > 0 else -1
#t_avg = round((tempC + tempW) / 2, 1) if tempC > 0 and tempW > 0 else -1

ts_end = now()
ts_diff = '{0:0.3f}'.format((ts_end - ts_start).total_seconds())

ts = ts_start.strftime("%Y%m%d%H%M%S")


# HEATER
if tempW > 0 and tempW < target_temp:
    setCtrl(pin_heater, 1)
else:
    setCtrl(pin_heater, 0)

# HUMIDIFIER
if h_avg > 0 and h_avg < target_hmdt:
    setCtrl(pin_humidifier, 1)
else:
    setCtrl(pin_humidifier, 0)

# LAMP
if t_avg > 0 and tempC <= cold_min_temp and 0 == now().minute % lamp_freq:
    setCtrl(pin_lamp, 1)
else:
    setCtrl(pin_lamp, 0)

# GET CONTROLS STATUSES
c_heater = getCtrl(pin_heater)
c_humidifier = getCtrl(pin_humidifier)
c_lamp = getCtrl(pin_lamp)

# OUTPUT
print ts, ts_diff, t_cold, h_cold, t_warm, h_warm, t_room, h_room, c_heater, c_humidifier, c_lamp

# SEND DATA
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
    #buzz_time += 1


if(buzz_time > 0):
    buzz(buzz_time)
    setCtrl(pin_warn, 1)
else:
    setCtrl(pin_warn, 0)

sys.exit(0)

