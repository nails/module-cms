<?php

	echo $this->load->view( 'structure/header', getControllerData() );
	echo $mainbody;
	echo $this->load->view( 'structure/footer', getControllerData() );