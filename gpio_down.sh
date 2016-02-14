#! /bin/sh

#sudo ln -s /home/pi/terrasens/gpio_down.sh /etc/init.d/gpio_down.sh
#sudo update-rc.d gpio_down.sh defaults

/usr/bin/gpio -g mode 2 out
/usr/bin/gpio -g write 2 0
/usr/bin/gpio -g mode 3 out
/usr/bin/gpio -g write 3 0
/usr/bin/gpio -g mode 4 out
/usr/bin/gpio -g write 4 0
/usr/bin/gpio -g mode 17 out
/usr/bin/gpio -g write 17 0
/usr/bin/gpio -g mode 27 out
/usr/bin/gpio -g write 27 0
/usr/bin/gpio -g mode 22 out
/usr/bin/gpio -g write 22 0


