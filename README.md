# dockerphp
Docker environment for Apache, PHP and Mysql

Steps to create php envoirnment
------------------------------------
1. First install docker and docker-compose on you machine
2. Clone this repository
 -- git clone https://github.com/alankar1985/dockerphp.git
3. After successfull clone, go inside docker php folder 
4. Now create volumns for Mysql and php files
  Mysql- /var/mysql/:/var/lib/mysql, Here /var/mysql/ is host machine volumn and /var/lib/mysq is docker container volumn
  PHP - /var/www/html/:/var/www/html/, /var/www/html is host machine volumn and /var/lib/mysq is docker container volumn 
  Note: You can keep any name to create volumn on Host machine
  
5. After volumn creation, go inside dockerphp folder and run below commands to start and stop the container
   To Start:- docker-compose up
   To Stop:- docker-compose down
