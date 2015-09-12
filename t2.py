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


def setCtrl(pinNum, state):
    if 0 == state:
        GPIO.setup(pinNum, GPIO.IN)
    elif 1 == state:
        GPIO.setup(pinNum, GPIO.OUT)
    else:
        pass

def getCtrl(pinNum):
    return int(GPIO.gpio_function(pinNum) == GPIO.OUT)



# SETUP

ts_start = now()
pwd = os.path.dirname(os.path.realpath(__file__))
ini = os.path.join(pwd,'terrasens.ini')
log = os.path.join(pwd,'logs','terrasens.log')

Config = ConfigParser.ConfigParser()
Config.read(ini)


class IniIntParser(ConfigParser.ConfigParser):
    def extract_ints(self):
        d = dict(self._sections)
        t = {}
        for k in d:
            d[k] = dict(self._defaults, **d[k])
            d[k].pop('__name__', None)
            for kk in d[k]:
                if d[k][kk].isdigit():
                    globals()['_' + k + '_' + kk] = int(d[k][kk])

f = IniIntParser()
f.read(ini)
f.extract_ints()
print locals()
#print d['sensors']['pin_warm']
sys.exit(0)


pin_warm = int(Config.get('sensors', 'pin_warm'))
pin_cold = int(Config.get('sensors', 'pin_cold'))
pin_room = int(Config.get('sensors', 'pin_room'))

warm_min = int(Config.get('temperature', 'warm_min'))
warm_max = int(Config.get('temperature', 'warm_max'))

h_avg_min = int(Config.get('humidity', 'avg_min'))
h_avg_max = int(Config.get('humidity', 'avg_max'))

pin_heater = int(Config.get('control', 'pin_heater'))
pin_humidifier = int(Config.get('control', 'pin_humidifier'))



print pin_warm, pin_cold, pin_room, warm_min, warm_max, h_avg_min, h_avg_max, pin_heater, pin_humidifier

# ACTION

GPIO.setmode(GPIO.BCM)
GPIO.setwarnings(False)

t_warm, h_warm = getsens(pin_warm)
t_cold, h_cold = getsens(pin_cold)
t_room, h_room = getsens(pin_room)

ts_end = now()
ts_diff = '{0:0.3f}'.format((ts_end - ts_start).total_seconds())

ts = ts_start.strftime("%Y%m%d%H%M%S")


tempW = float(t_warm)
h_avg = (float(h_cold) + float(h_warm)) / 2


if tempW > warm_max:
    setCtrl(pin_heater, 0)
    print 'T off'
elif tempW < warm_min:
    setCtrl(pin_heater, 1)
    print 'T on'
else:
	print 'T OK', tempW, warm_min, warm_max

if h_avg > h_avg_max:
    setCtrl(pin_humidifier, 0)
    print 'H off', h_avg, h_avg_max
elif h_avg < h_avg_min:
    setCtrl(pin_humidifier, 1)
    print 'H on', h_avg, h_avg_min
else:
	print 'H OK', h_avg, h_avg_min, h_avg_max


print ts, ts_diff, t_cold, h_cold, t_warm, h_warm, t_room, h_room, getCtrl(pin_heater), getCtrl(pin_humidifier)

# print GPIO.gpio_function(25) == GPIO.IN
sys.exit(0)

