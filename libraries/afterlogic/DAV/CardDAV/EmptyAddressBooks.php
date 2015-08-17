<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace afterlogic\DAV\CardDAV;

class EmptyAddressBooks extends \Sabre\CardDAV\UserAddressBooks{

	/**
     * Returns a list of addressbooks
     *
     * @return array
     */
    public function getChildren() {

        return array();

    }

}
