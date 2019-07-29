//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class bitracker_hook_nexusPackage extends _HOOK_CLASS_
{
	/**
	 * Delete Package Data
	 *
	 * @return	void
	 */
	public function delete()
	{
		try
		{
			/* Let parent do its thing first */
			parent::delete();
	
			/* Look for files linked to this product */
			foreach( \IPS\Db::i()->select( '*', 'bitracker_torrents', array( \IPS\Db::i()->findInSet( 'file_nexus', array( $this->id ) ) ) ) as $file )
			{
				$packages = explode( ',', $file['file_nexus'] );
				$packages = array_filter( $packages, function( $packageId ){
					return $packageId != $this->id;
				} );
	
				/* If the only package was the one we just deleted, set it as not purchasable but do not remove the file_nexus value or the product will become downloadable by anyone */
				if( !\count( $packages ) )
				{
					\IPS\Db::i()->update( 'bitracker_torrents', array( 'file_purchasable' => 0 ), array( 'file_id=?', $file['file_id'] ) );
				}
				/* Otherwise if there are other products that can be purchased, remove this product from the purchasable list */
				else
				{
					\IPS\Db::i()->update( 'bitracker_torrents', array( 'file_nexus' => implode( ',', $packages ) ), array( 'file_id=?', $file['file_id'] ) );
				}
			}
		}
		catch ( \RuntimeException $e )
		{
			if ( method_exists( get_parent_class(), __FUNCTION__ ) )
			{
				return \call_user_func_array( 'parent::' . __FUNCTION__, \func_get_args() );
			}
			else
			{
				throw $e;
			}
		}
	}
}