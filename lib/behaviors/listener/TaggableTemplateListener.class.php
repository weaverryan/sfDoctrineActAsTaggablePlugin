<?php
/*
 * This file is part of the sfDoctrineActAsTaggablePlugin package.
 * But is fully inspired by sfPropelActAsTaggableBehavior package.
 *
 * (c) 2007 Xavier Lacot <xavier@lacot.org> - sfPropelActAsTaggableBehavior
 * (c) 2008 Mickael Kurmann <mickael.kurmann@gmail.com> - sfDoctrineActAsTaggablePlugin
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 * This behavior permits to attach tags to Doctrine objects. Some more bits about
 * the philosophy of the stuff:
 *
 * - taggable objects must have a primary key
 * - tags are saved when the object is saved, not before
 *
 * - one object cannot be tagged twice with the same tag. When trying to use
 *   twice the same tag on one object, the second tagging will be ignored
 *
 * - the tags associated to one taggable object are only loaded when necessary.
 *   Then they are cached.
 *
 * - once created, tags never change in the Tag table. When using replaceTag(),
 *   a new tag is created if necessary, but the old one is not deleted.
 *
 *
 * The plugin associates a parameterHolder to Propel objects, with 3 namespaces:
 *
 * - tags:
 *     Tags that have been attached to the object, but not yet saved.
 *     Contract: tags are disjoin of (saved_tags union removed_tags)
 *
 * - saved_tags:
 *     Tags that are presently saved in the database
 *
 * - removed_tags:
 *     Tags that are presently saved in the database, but which will be removed
 *     at the next save()
 *     Contract: removed_tags are disjoin of (tags union saved_tags)
 *
 *
 * @author   Xavier Lacot <xavier@lacot.org>
 * @see      http://www.symfony-project.com/trac/wiki/sfDoctrineActAsTaggablePlugin
 */
 
 
class TaggableListener extends Doctrine_Record_Listener
{
    /**
    * Tags saving logic, runned after the object himself has been saved
    *
    * @param      Doctrine_Event  $event
    */
    public function postSave(Doctrine_Event $event)
    {

        $object = $event->getInvoker();
        
        $added_tags = Taggable::get_tags($object);
        $removed_tags = array_keys(Taggable::get_removed_tags($object));
        
        // save new tags
        foreach ($added_tags as $tagname)
        {
            $tag = PluginTagTable::findOrCreateByTagName($tagname);
            $tag->save();
            $tagging = new Tagging();
            $tagging->tag_id = $tag->id;
            $tagging->taggable_id = $object->id;
            $tagging->taggable_model = get_class($object);
            $tagging->save();
        }
        
        if($removed_tags)
        {
            $q = Doctrine_Query::create()->select('t.id')
                ->from('Tag t INDEXBY t.id')
                ->whereIn('t.name', $removed_tags);
								
            $removed_tag_ids = array_keys($q->execute(array(), Doctrine::HYDRATE_ARRAY));
            
            Doctrine::getTable('Tagging')->createQuery()
	            ->delete()
	            ->whereIn('tag_id', $removed_tag_ids)
	            ->addWhere('taggable_id = ?', $object->id)
	            ->addWhere('taggable_model = ?', get_class($object))
	            ->execute();
        }

        $tags = array_merge(Taggable::get_tags($object) , $object->getSavedTags());
        
        Taggable::set_saved_tags($object, $tags);
        Taggable::clear_tags($object);
        Taggable::clear_removed_tags($object);
    }

    /**
    * Delete related Taggings when this object is deleted
    *
    * @param      Doctrine_Event $event
    */
    public function preDelete(Doctrine_Event $event)
    {
      
        $object = $event->getInvoker();
        
        Doctrine::getTable('Tagging')->createQuery()
          ->delete()
          ->addWhere('taggable_id = ?', $object->id)
          ->addWhere('taggable_model = ?', get_class($object))
          ->execute();
    }
}
