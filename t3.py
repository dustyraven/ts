#!/usr/bin/python
import sys
import datetime
import Adafruit_DHT
import RPi.GPIO as GPIO

def getsens( pin ):

    hmdt, temp = Adafruit_DHT.read_retry(Adafruit_DHT.DHT22, pin)

    t = '{0:0.2f}'.format(temp) if temp is not None else -1
    h = '{0:0.2f}'.format(hmdt) if hmdt is not None else -1

    return t, h

GPIO.setmode(GPIO.BCM)

ts_start = datetime.datetime.now()

t1, h1 = getsens(17)
t2, h2 = getsens(18)
t3, h3 = getsens(23)

ts_end = datetime.datetime.now()
ts_diff = '{0:0.3f}'.format((ts_end - ts_start).total_seconds())

ts = ts_start.strftime("%Y%m%d%H%M%S")

print ts, ts_diff, t1, h1, t2, h2, t3, h3

t2 = float(t2)

if t2 > 30:
    GPIO.setup(25, GPIO.IN)
    print t2, ' > 30'
if t2 < 28:
    GPIO.setup(25, GPIO.OUT)
    print t2, ' < 28'


sys.exit(0)

