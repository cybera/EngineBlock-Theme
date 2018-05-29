FROM centos:7

RUN curl --silent --location https://rpm.nodesource.com/setup_8.x | bash -
RUN yum install -y epel-release && \
  yum install -y nodejs gem ruby-devel libffi-devel gcc make && \
  npm install -g grunt grunt-cli browserify && \
  gem install compass

RUN mkdir /eb

CMD /eb/docker-start.sh
