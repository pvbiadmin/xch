<?php

namespace BPL\Lib\Local\Database;

interface Db_Info
{
	public const HOST = 'localhost';
	public const USERNAME = 'root';
	public const PASSWORD = '';
	public const DATABASE = 'escudero2_db';

	public static function connect();
}