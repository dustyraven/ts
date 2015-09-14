#! /bin/sh

#sudo ln -s /home/pi/terrasens/gpio_down.sh /etc/init.d/gpio_down.sh
#sudo update-rc.d gpio_down.sh defaults

gpio -g mode 2 out
gpio -g write 2 0
gpio -g mode 3 out
gpio -g write 3 0
gpio -g mode 4 out
gpio -g write 4 0
gpio -g mode 17 out
gpio -g write 17 0
gpio -g mode 27 out
gpio -g write 27 0
gpio -g mode 22 out
gpio -g write 22 0


