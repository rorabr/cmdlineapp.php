# Makefile for installing cmdlineapp.php - https://github.com/rorabr/cmdlineapp.php
# Must be run as root to install in PHP's library directory

f=cmdlineapp.php
php=$(shell which php)
src=src/${f}

all: check install

# perform some checks on the source, php and destination
check:
	$(if ${php},,$(error php executable not found))
	$(if $(wildcard ${src}),,$(error source file ${src} not found, make sould be run in the project\'s main directory))
	$(eval dst := $(shell php -r '$$a=explode(":",get_include_path());while(($$p=array_shift($$a))=="."){}print $$p;'))
	$(if ${dst},,$(error instalation directory could not be determined by PHP\'s include paths))
	$(eval owner := $(shell ls -lad ${dst} | awk '{print $$3}'))
	$(eval group := $(shell ls -lad ${dst} | awk '{print $$4}'))
	@echo "Instalation checks ok"

install:
	install -D --mode=0644 --owner=${owner} --group=${group} ${src} ${dst}/rora/${f}
	@echo "File ${f} installed on ${dst}"
