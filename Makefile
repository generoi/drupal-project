include lib/genero-conf/Makefile.drupal.mk

all: check

# Sanity checks
check: DRUSH-exists SSHAGENT-exists LOCAL-env

# Virtual Machine -------------------------------------------------------------
#
# To scaffold the files for the VM run `make vm`. After that simply provision
# it.

vm: check
	cp -r lib/drupal-vm vm
	ln -sf ../config/drupal-vm.config.yml vm/config.yml

vm-clean:
	rm -rf vm
