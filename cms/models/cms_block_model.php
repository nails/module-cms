<?php

/**
 * This model handle CMS Blocks
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Cms_block_model1 extends NAILS_Model
{
    /**
     * Creates a new block object
     * @param  string $type         The type of content the bock is
     * @param  string $slug         The slug to use for the block
     * @param  string $title        The title of the block
     * @param  string $description  The description of the block (i.e what it should represent)
     * @param  string $located      A description of where the block is intended to be seen
     * @param  string $defaultValue The value of the block in the app's default langauge
     * @param  bool   $returnObject Whether or not to return just the ID of the newly created object (false) or the entire object (true)
     * @return mixed
     **/
    public function create_block($type, $slug, $title, $description, $located, $defaultValue, $returnObject = false)
    {
        //  Test the slug
        if ($this->get_by_slug($slug)) {

            return false;
        }

        // --------------------------------------------------------------------------

        $this->db->set('type', $type);
        $this->db->set('slug', $slug);
        $this->db->set('title', $title);
        $this->db->set('description', $description);
        $this->db->set('located', $located);
        $this->db->set('created', 'NOW()', false);
        $this->db->set('modified', 'NOW()', false);

        if (active_user('id')) {

            $this->db->set('created_by', active_user('id'));
            $this->db->set('modified_by', active_user('id'));
        }

        $this->db->insert(NAILS_DB_PREFIX . 'cms_block');

        if ($this->db->affected_rows()) {

            $id = $this->db->insert_id();

            $this->db->set('block_id', $id);
            $this->db->set('language', $this->language_model->getDefaultCode());
            $this->db->set('value', $defaultValue);
            $this->db->set('created', 'NOW()', false);
            $this->db->set('modified', 'NOW()', false);

            if (active_user('id')) {

                $this->db->set('created_by', active_user('id'));
                $this->db->set('modified_by', active_user('id'));
            }

            $this->db->insert(NAILS_DB_PREFIX . 'cms_block_translation');

            if ($this->db->affected_rows()) {

                if ($returnObject) {

                    return $this->get_by_id($id);

                } else {

                    return $id;
                }

            } else {

                $this->db->where('id', $id);
                $this->db->delete(NAILS_DB_PREFIX . 'cms_block');
                $this->_set_error('Failed to add translation');
                return false;
            }

        } else {

            $this->_set_error('Failed to create block');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Updates an existing block object
     * @param  int   $id   The ID of the block
     * @param  mixed $data The fields to update
     * @return bool
     **/
    public function update_block($id, $data = array())
    {
        //  Can't change some things
        unset($data['id']);
        unset($data['created']);
        unset($data['created_by']);

        $this->db->set($data);
        $this->db->set('modified', 'NOW()', false);

        if (active_user('id')) {

            $this->db->set('modified_by', active_user('id'));

        } else {

            $this->db->set('modified_by', null);
        }

        $this->db->where('id', $id);
        $this->db->update(NAILS_DB_PREFIX . 'cms_block');

        return (bool) $this->db->affected_rows();
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a block object
     * @param  mixed $idSlug The ID, or slug, of the block to delete
     * @return bool
     **/
    public function delete_block($idSlug)
    {
        if (is_numeric($idSlug)) {

            $this->db->where('id', $idSlug);

        } else {

            $this->db->where('slug', $idSlug);
        }

        $this->db->delete(NAILS_DB_PREFIX . 'cms_block');

        return (bool) $this->db->affected_rows();
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new translation object
     * @param  int    $blockId  The ID of the block this translation belongs to
     * @param  int    $language The ID of the language this block is written in
     * @param  string $value    The contents of this translation
     * @return mixed
     **/
    public function create_translation($blockId, $language, $value)
    {
        $this->db->set('block_id', $blockId);
        $this->db->set('language', $language);
        $this->db->set('value', trim($value));
        $this->db->set('created', 'NOW()', false);
        $this->db->set('modified', 'NOW()', false);

        if (active_user('id')) {

            $this->db->set('created_by', active_user('id'));
            $this->db->set('modified_by', active_user('id'));

        } else {

            $this->db->set('created_by', null);
            $this->db->set('modified_by', null);
        }

        $this->db->insert(NAILS_DB_PREFIX . 'cms_block_translation');

        if ($this->db->affected_rows()) {

            //  Upate the main block's modified date and user
            $this->update_block($blockId);

            return true;

        } else {

            $this->_set_error('Failed to create translation.');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Updates an existing translation object
     * @param  int    $blockId  The ID of the block this translation belongs to
     * @param  int    $language The ID of the language this block is written in
     * @param  string $value    The contents of this translation
     * @return bool
     **/
    public function update_translation($blockId, $language, $value)
    {
        //  Get existing translation
        $this->db->where('block_id', $blockId);
        $this->db->where('language', $language);
        $old = $this->db->get(NAILS_DB_PREFIX . 'cms_block_translation')->row();

        if (!$old){

            $this->_set_error('Could not find existing translation');
            return false;
        }

        // --------------------------------------------------------------------------

        //  If the value hasn't changed then don't do anything
        if ($old->value == trim($value)){

            $this->_set_error('Value has not changed.');
            return false;
        }

        // --------------------------------------------------------------------------

        $this->db->set('value', trim($value));
        $this->db->set('modified', 'NOW()', false);

        if (active_user('id')) {

            $this->db->set('modified_by', active_user('id'));

        } else {

            $this->db->set('modified_by', null);
        }

        $this->db->where('block_id', $block_id);
        $this->db->where('language', $language);
        $this->db->update(NAILS_DB_PREFIX . 'cms_block_translation');

        if ($this->db->affected_rows()) {

            //  Create a new revision if value has changed
            $this->db->select('id');
            $this->db->where('block_id', $block_id);
            $this->db->where('language', $language);
            $blockTranslation = $this->db->get(NAILS_DB_PREFIX . 'cms_block_translation')->row();

            if ($blockTranslation) {

                $this->db->set('block_translation_id', $blockTranslation->id);
                $this->db->set('value', $old->value);
                $this->db->set('created', $old->modified);
                $this->db->set('created_by', $old->modified_by);
                $this->db->insert(NAILS_DB_PREFIX . 'cms_block_translation_revision');

                //  Upate the main block's modified date and user
                $this->update_block($old->block_id);
            }

            return true;

        } else {

            $this->_set_error('Failed to update translation.');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Updates an existing object
     * @param  int  $blockId  The ID of the block the translation belongs to
     * @param  int  $language The language ID of the block
     * @return bool
     **/
    public function delete_translation($blockId, $language)
    {
        $this->db->where('block_id', $blockId);
        $this->db->where('language', $language);
        $this->db->delete(NAILS_DB_PREFIX . 'cms_block_translation');

        if ($this->db->affected_rows()) {

            //  Upate the main block's modified date and user
            $this->update_block($blockId);

            return true;

        } else {

            $this->_set_error('Failed to delete translation.');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all objects
     * @param  bool  $includeRevisions Whether to include translation revisions
     * @return array
     **/
    public function get_all($includeRevisions = false)
    {
        $this->db->select('cb.type, cb.slug, cb.title, cb.description, cb.located, cbv.*, u.first_name, ue.email, u.last_name, u.gender, u.profile_img');

        $this->db->join(NAILS_DB_PREFIX . 'cms_block cb', 'cb.id = cbv.block_id');
        $this->db->join(NAILS_DB_PREFIX . 'user u', 'u.id = cbv.created_by', 'LEFT');
        $this->db->join(NAILS_DB_PREFIX . 'user_email ue', 'ue.user_id = u.id AND ue.is_primary = 1', 'LEFT');

        $this->db->order_by('cb.title');

        $blocks = $this->db->get(NAILS_DB_PREFIX . 'cms_block_translation cbv')->result();

        $_out = array();

        for ($i=0; $i < count($blocks); $i++) {

            if (!isset($_out[$blocks[$i]->block_id])) {

                 $_out[$blocks[$i]->block_id]               = new stdClass();
                 $_out[$blocks[$i]->block_id]->id           = $blocks[$i]->block_id;
                 $_out[$blocks[$i]->block_id]->type         = $blocks[$i]->type;
                 $_out[$blocks[$i]->block_id]->slug         = $blocks[$i]->slug;
                 $_out[$blocks[$i]->block_id]->title        = $blocks[$i]->title;
                 $_out[$blocks[$i]->block_id]->description  = $blocks[$i]->description;
                 $_out[$blocks[$i]->block_id]->located      = $blocks[$i]->located;
                 $_out[$blocks[$i]->block_id]->translations = array();
            }

            $_temp                    = new stdClass();
            $_temp->id                = (int) $blocks[$i]->id;
            $_temp->value             = $blocks[$i]->value;
            $_temp->language          = $blocks[$i]->language;
            $_temp->created           = $blocks[$i]->created;
            $_temp->modified          = $blocks[$i]->modified;
            $_temp->user              = new stdClass();
            $_temp->user->id          = $blocks[$i]->created_by ? (int) $blocks[$i]->created_by : null;
            $_temp->user->email       = $blocks[$i]->email;
            $_temp->user->first_name  = $blocks[$i]->first_name;
            $_temp->user->last_name   = $blocks[$i]->last_name;
            $_temp->user->gender      = $blocks[$i]->gender;
            $_temp->user->profile_img = $blocks[$i]->profile_img;

            // --------------------------------------------------------------------------

            //  Save the default version
            if ($blocks[$i]->language == APP_DEFAULT_LANG_CODE) {

                $_out[$blocks[$i]->block_id]->default_value = $blocks[$i]->value;
            }

            // --------------------------------------------------------------------------

            //  Are we including revisions?
            if ($includeRevisions) {

                $this->db->select('cbtr.*, ue.email, u.first_name, u.last_name, u.gender, u.profile_img');
                $this->db->where('cbtr.block_translation_id', $blocks[$i]->id);
                $this->db->join(NAILS_DB_PREFIX . 'user u', 'u.id = cbtr.created_by', 'LEFT');
                $this->db->join(NAILS_DB_PREFIX . 'user_email ue', 'ue.user_id = u.id AND ue.is_primary = 1', 'LEFT');
                $this->db->order_by('created', 'DESC');
                $_temp->revisions = $this->db->get(NAILS_DB_PREFIX . 'cms_block_translation_revision cbtr')->result();

                foreach ($_temp->revisions as $revision) {

                    $revision->user              = new stdClass();
                    $revision->user->id          = $revision->created_by ? (int) $revision->created_by : null;
                    $revision->user->email       = $revision->email;
                    $revision->user->first_name  = $revision->first_name;
                    $revision->user->last_name   = $revision->last_name;
                    $revision->user->gender      = $revision->gender;
                    $revision->user->profile_img = $revision->profile_img;

                    unset($revision->created_by);
                    unset($revision->email);
                    unset($revision->first_name);
                    unset($revision->last_name);
                    unset($revision->gender);
                    unset($revision->profile_img);
                }

                if ($blocks[$i]->language == APP_DEFAULT_LANG_CODE) {

                    $_out[$blocks[$i]->block_id]->default_value_revisions = $_temp->revisions;
                }
            }

            $_out[$blocks[$i]->block_id]->translations[] = $_temp;
        }

        // --------------------------------------------------------------------------

        return array_values($_out);
    }

    // --------------------------------------------------------------------------

    /**
     * Fetch an object by it's ID
     * @param  int      $id               The ID of the object to fetch
     * @param  bool     $includeRevisions Whether to include translation revisions
     * @return stdClass
     **/
    public function get_by_id($id, $includeRevisions = false)
    {
        $this->db->where('cb.id', $id);
        $result = $this->get_all($includeRevisions);

        // --------------------------------------------------------------------------

        if (!$result) {

            return false;
        }

        // --------------------------------------------------------------------------

        return $result[0];
    }

    // --------------------------------------------------------------------------

    /**
     * Fetch an object by it's slug
     * @param  string   $slug             The slug of the object to fetch
     * @param  bool     $includeRevisions Whether to include translation revisions
     * @return stdClass
     **/
    public function get_by_slug($slug, $includeRevisions = false)
    {
        $this->db->where('cb.slug', $slug);
        $result = $this->get_all($includeRevisions);

        // --------------------------------------------------------------------------

        if (!$result) {

            return false;
        }

        // --------------------------------------------------------------------------

        return $result[0];
    }
}

class NAILS_Cms_block_model extends NAILS_Model
{
    /**
     * Model constructor
     **/
    public function __construct()
    {
        parent::__construct();
        $this->_table = NAILS_DB_PREFIX . 'cms_block';
        $this->_table_prefix = 'b';
    }

    // --------------------------------------------------------------------------

    /**
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     * @param array  $data    Data passed from the calling method
     * @param string $_caller The name of the calling method
     * @return void
     **/
    protected function _getcount_common($data = array(), $_caller = null)
    {
        if (!empty($data['keywords'])) {

            if (!isset($data['or_like'])) {

                $data['or_like'] = array();
            }

            $data['or_like'][] = array('b.label', $data['keywords']);
            $data['or_like'][] = array('b.value', $data['keywords']);
            $data['or_like'][] = array('b.located', $data['keywords']);
            $data['or_like'][] = array('b.description', $data['keywords']);
        }

        parent::_getcount_common($data, $_caller);
    }
}

// --------------------------------------------------------------------------

/**
 * OVERLOADING NAILS' MODELS
 *
 * The following block of code makes it simple to extend one of the core Nails
 * models. Some might argue it's a little hacky but it's a simple 'fix'
 * which negates the need to massively extend the CodeIgniter Loader class
 * even further (in all honesty I just can't face understanding the whole
 * Loader class well enough to change it 'properly').
 *
 * Here's how it works:
 *
 * CodeIgniter instantiate a class with the same name as the file, therefore
 * when we try to extend the parent class we get 'cannot redeclare class X' errors
 * and if we call our overloading class something else it will never get instantiated.
 *
 * We solve this by prefixing the main class with NAILS_ and then conditionally
 * declaring this helper class below; the helper gets instantiated et voila.
 *
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if (!defined('NAILS_ALLOW_EXTENSION_CMS_BLOCK_MODEL')) {

    class Cms_block_model extends NAILS_Cms_block_model
    {
    }
}
