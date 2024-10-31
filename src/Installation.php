<?php
namespace PostReplicator;

class Installation
{
	public static function install()
	{
		DB::create_tables();
	}

	public static function uninstall()
	{
		DB::drop_tables();
	}
}