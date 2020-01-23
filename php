<?php
$ip = $_SERVER['REMOTE_ADDR'];
echo "<h1> Hello world from PHP </br> u reached me from $ip</h1>";

   $dbhost = 'apache-sql:3306';
   $dbuser = 'root';
      $dbpass = 'root';
      $conn = mysqli_connect($dbhost, $dbuser, $dbpass);

      if(! $conn ) {
                       echo "Connection failure";
                       die('Could not connect: ' . mysqli_error($conn));
                          }

         echo 'Connected successfully';
         mysqli_close($conn);



?>

########################################################################################################################

cat alter_db
mysql -h"apache-sql" -p"######"<<EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '######';
ALTER USER 'root'@'%' IDENTIFIED WITH mysql_native_password BY '######';
EOF


##########################################################################################################################


cat recreate.sh
sudo docker network ls || sudo docker network create php-sql

sudo docker ps | awk '{print $1}' | grep -v CONTAINER | xargs sudo docker rm -f

sudo docker ps -a | awk '{print $1}' | grep -v CONTAINER | xargs sudo docker rm


sudo docker rmi  apache-sql -f

cd /home/ubuntu/mysql-developer
sudo docker build -t apache-sql .

sudo docker run -it -d --network php-sql --name apache-sql apache-sql

sleep 60

cd /home/ubuntu/php-developer

sudo docker rmi apache-php -f


sudo docker build -t apache-php .

sudo docker run -it -d --network php-sql -p 8080:80 apache-php


############################################################################################################################


cat Dockerfile
FROM ubuntu:latest
RUN ln -s  /usr/share/zoneinfo/Asia/Kolkata /etc/localtime
RUN apt update -y && apt install php libapache2-mod-php php-mysql curl apache2 mysql-client -y
RUN service apache2 start
RUN rm -f /var/www/html/index.html
COPY index.php /var/www/html/index.php
ADD alter_db  /tmp/alter_db
RUN chmod 755 /tmp/alter_db
#RUN . /tmp/alter_db

RUN echo '. /etc/apache2/envvars' > /root/run_apache.sh && \
 echo 'mkdir -p /var/run/apache2' >> /root/run_apache.sh && \
 echo 'mkdir -p /var/lock/apache2' >> /root/run_apache.sh && \
 echo 'sh /tmp/alter_db' >> /root/run_apache.sh && \
 echo '/usr/sbin/apache2 -D FOREGROUND' >> /root/run_apache.sh && \
 chmod 755 /root/run_apache.sh

EXPOSE 80

CMD /root/run_apache.sh


#########################################################################################################################


cat Dockerfile

FROM mysql
ENV MYSQL_ROOT_PASSWORD root
ADD alter_db /tmp/alter_db
ADD start.sh /tmp/start.sh
#ENTRYPOINT  ["/tmp/start.sh"]







