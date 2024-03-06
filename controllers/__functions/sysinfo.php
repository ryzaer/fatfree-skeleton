<?php function sysinfo($bool=false){
	switch (true) {
		case stristr(PHP_OS, 'WIN'): return !$bool ? 'win' : 1;
		case stristr(PHP_OS, 'DAR'): return !$bool ? 'osx' : 2;
		case stristr(PHP_OS, 'LINUX'): return !$bool ? 'unix' : 3;
		default : return !$bool ? 'unknown' : 0 ;
	}	
};