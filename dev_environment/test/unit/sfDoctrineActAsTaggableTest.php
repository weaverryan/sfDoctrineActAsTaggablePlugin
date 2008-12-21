<?php

include(dirname(__FILE__).'/../bootstrap/unit.php');

$configuration = ProjectConfiguration::getApplicationConfiguration('frontend', 'test', true);
new sfDatabaseManager($configuration);

Doctrine::loadModels($configuration->getRootDir() . '/lib/model/doctrine');
Doctrine::dropDatabases();
Doctrine::createDatabases();
Doctrine::createTablesFromModels();
Doctrine::loadData($configuration->getRootDir() . '/data/fixtures');

// test variables definition
define('TEST_CLASS',      'BlogPost');
define('TEST_CLASS_2',    'BlogComment');
define('DOCTRINE_CLASS',  'UnTaggableModel'); # a doctrine class not taggable please :D

if (!defined('TEST_CLASS') || !class_exists(TEST_CLASS)
    || !defined('TEST_CLASS_2') || !class_exists(TEST_CLASS_2))
{
  // Don't run tests
  throw new exception('test classes do not exist!');
}

// initialize database manager
// $databaseManager = new sfDatabaseManager();
// $databaseManager->initialize();

// clean the database
//Doctrine::getTable('Tagging')->findAll()->delete();
//Doctrine::getTable('Tag')->findAll()->delete();
//Doctrine::getTable(TEST_CLASS)->findAll()->delete();

// start tests
$t = new lime_test(91, new lime_output_color());

$object = _create_object(TEST_CLASS);
$object->title = 'object';


// #### Testing TaggableToolKit basics ####

$t->diag('Testing TaggableToolKit basics');

// TaggableToolkit::cleanTagName()
$t->diag('TaggableToolkit::cleanTagName()');
  $t->is(TaggableToolkit::cleanTagName(' loutre '), 'loutre', 'Tag has no space at the end and at the start');
  $t->is(TaggableToolkit::cleanTagName(' loutre, '), 'loutre', 'Tag "," are removed');

// TaggableToolkit::explodeTagString()
  $t->diag('TaggableToolkit::explodeTagString()');
  $t->is(TaggableToolkit::explodeTagString('  loutre
  bonjour,monsieur'), array('loutre','bonjour','monsieur'), '",", carriage return and tabulation are replaced');
  $t->is(TaggableToolkit::explodeTagString('loutre, bonjour  monsieur'), array('loutre','bonjour  monsieur'), 'multiple space stay');

// TaggableToolkit::extractTriple()
$t->diag('TaggableToolkit::extractTriple()');
  $t->ok(TaggableToolkit::extractTriple('ns:key=value') === array('ns:key=value', 'ns', 'key', 'value'), 'triple extracted successfully.');
  $t->ok(TaggableToolkit::extractTriple('ns:key') === array('ns:key', null, null, null), 'ns:key is not a triple.');
  $t->ok(TaggableToolkit::extractTriple('ns') === array('ns', null, null, null), 'ns is not a triple.');

// TaggableToolkit::formatTagString()
  $t->diag('TaggableToolkit::formatTagString()');
  $t->is(TaggableToolkit::formatTagString(array('tag1','tag2','tag3')), '"tag1", "tag2" and "tag3"', 'format an array in a string list');
  $t->is(TaggableToolkit::formatTagString('tag1,tag2,tag3'), '"tag1", "tag2" and "tag3"', 'format a string in a string list');

// TaggableToolkit::isTaggable()
  $t->diag('TaggableToolkit::isTaggable()');
  $t->ok(TaggableToolkit::isTaggable($object), 'Object ' . TEST_CLASS . ' is taggable');
  $t->ok(TaggableToolkit::isTaggable(TEST_CLASS), 'Object string ' . TEST_CLASS . ' is taggable');

  $object_loutre = _create_object(DOCTRINE_CLASS);

  $t->ok(!TaggableToolkit::isTaggable($object_loutre), 'Object ' . DOCTRINE_CLASS . ' is not taggable');
  $t->ok(!TaggableToolkit::isTaggable(DOCTRINE_CLASS), 'Object string ' . DOCTRINE_CLASS . ' is not taggable');



// #### Testing PlaceHolder Mehods ####

$t->diag('Testing PlaceHolder Mehods');

  $t->is(Taggable::get_tags($object), array(), 'a new object has no tag in memory');
  Taggable::set_tags($object, array("loutre" => 'loutre'));
  $t->is(Taggable::get_tags($object), array('loutre' => 'loutre'), 'loutre is in memory from get_tags');
  $t->ok($object->hasTag('loutre'), 'tag loutre in tag list');
  Taggable::set_tags($object, array('irma' => 'irma', 'mourte' => 'mourte'));
  $t->is(Taggable::get_tags($object), array('irma' => 'irma', 'mourte' => 'mourte'), 'get_tags retrieve an array of tags, set_tags remove older tags');
  $t->ok(!$object->hasTag('loutre'), 'tag loutre no more in tag list');
  Taggable::add_tag($object, 'added');
  $t->is(Taggable::get_tags($object), array('irma' => 'irma', 'mourte' => 'mourte', 'added' => 'added'), 'adding a tag ok');
  
  Taggable::set_saved_tags($object, array('saved1' => 'saved1'));
  $t->is(Taggable::get_saved_tags($object), array('saved1' => 'saved1'), 'setting saved_tag ok');
  Taggable::add_saved_tag($object, 'saved2');
  $t->is(Taggable::get_saved_tags($object), array('saved1' => 'saved1', 'saved2' => 'saved2'), 'adding a saved_tag ok');
  $t->ok($object->hasTag('saved1'), 'tag saved1 in saved_tag list');
  
  Taggable::set_removed_tags($object, array('removed1' => 'removed1'));
  $t->is(Taggable::get_removed_tags($object), array('removed1' => 'removed1'), 'setting removed_tag ok');
  Taggable::add_removed_tag($object, 'removed2');
  $t->is(Taggable::get_removed_tags($object), array('removed1' => 'removed1', 'removed2' => 'removed2'), 'adding a removed_tag ok');
  $t->ok(!$object->hasTag('removed1'), 'tag removed1 in removed_tag list, but hasTag wont take it');
  
  //clear_tags()
  Taggable::clear_tags($object);
  $t->is(Taggable::get_tags($object), array(), 'object has no more tags in memory');
  //clear_saved_tags()
  Taggable::clear_saved_tags($object);
  $t->is(Taggable::get_saved_tags($object), array(), 'object has no more saved_tags in memory');
  //clear_removed_tags()
  Taggable::clear_removed_tags($object);
  $t->is(Taggable::get_removed_tags($object), array(), 'object has no more removed_tags in memory');


// #### Testing object methods ####

$t->diag('Testing object methods');

  $object->addTag('toto');
  $object_tags = $object->getTags();
  $t->ok((count($object_tags) == 1) && ($object_tags['toto'] == 'toto'), 'a non-saved object can get tagged.');
  
  $object->addTag('toto');
  $object_tags = $object->getTags();
  $t->ok(count($object_tags) == 1, 'a tag is only applied once to non-saved objects.');
  $object->save();
  
  $object->addTag('toto');
  $object_tags = $object->getTags();
  $t->ok(count($object_tags) == 1, 'a tag is also only applied once to saved objects.');
  $object->save();
  
  $object->addTag('tutu');
  $object_tags = $object->getTags();
  $t->ok($object->hasTag('tutu'), 'a saved object can get tagged.');
  $object->save();
  
  // get the key of this object
  $id1 = $object->id;
  
  $object->removeTag('tutu');
  $t->ok(!$object->hasTag('tutu'), 'a previously saved tag can be removed.');
  
  $object->addTag('tata');
  $object->removeTag('tata');
  $t->ok(!$object->hasTag('tata'), 'a non-saved tag can also be removed.');
  
  $object->addTag('tu\'tu');
  $t->ok($object->hasTag('tu\'tu'), 'a tag can contain a quote.');
  
  $object2 = _create_object(TEST_CLASS_2);
  $object2->title = 'object2';
  $object_tags = $object2->getTags();
  $t->ok(count($object_tags) == 0, 'a new object has no tag, even if other tagged objects exist.');
  $object2->save();
  
  $object2->addTag('titi');
  $t->ok($object2->hasTag('titi'), 'a new object can get tagged, even if other tagged objects exist.');
  $t->ok(!$object->hasTag('titi'), 'tags applied to new objects do not affect old ones.');
  $object2->save();
  
  $id2 = $object2->id;
  
  /*
   * Klemens 2008-12-03: This doesn't make sense with doctrine, as find returns a reference on the existing object 
   *
  $object2_copy = Doctrine::getTable(TEST_CLASS_2)->find($id2);
  $object2_copy->title = 'object2_copy';
  $object2_copy->addTag('clever');
  $t->ok($object2_copy->hasTag('clever') && !$object2->hasTag('clever'), 'tags are applied to the object instances independently.');
  */
  
  $object = _create_object(TEST_CLASS);
  $object->addTag('tutu');
  $object->addTag('titi');
  $object->save();
  $object->addTag('tata');
  $object->removeAllTags();
  $t->ok(!$object->hasTag('titi'), 'tags can all be removed at once.');
  
  $object = _create_object(TEST_CLASS);
  $object->addTag('toto,international,tata');
  $object->save();
  $id = $object->id;
  $object = Doctrine::getTable(TEST_CLASS)->find($id);
  $object->removeTag('tata');
  $object->addTag('tata');
  $object->save();
  $object = Doctrine::getTable(TEST_CLASS)->find($id);
  $object_tags = $object->getTags();
  $t->ok(count($object_tags) == 3, 'when removing one previously saved tag, then restoring it, and then saving it again, this tag is not duplicated.');
  
  $object = _create_object(TEST_CLASS);
  $object->addTag('toto,tutu,tata');
  $object->save();
  $object->removeAllTags();
  $object->addTag('toto,tutu,tata');
  $object->save();
  $id = $object->id;
  $object = Doctrine::getTable(TEST_CLASS)->find($id);
  $object_tags = $object->getTags();
  $t->ok(count($object_tags) == 3, 'when removing all previously saved tags, then restoring it, and then saving it again, tags are not duplicated.');
  
  $object = _create_object(TEST_CLASS);
  $object->addTag('toto,tutu,tata');
  $object->save();
  $previous_count = count($object->getTags());
  $object->removeAllTags();
  $object->save();
  $id = $object->id;
  $object = Doctrine::getTable(TEST_CLASS)->find($id);
  $t->ok(($previous_count == 3) && !$object->hasTag(), 'previously in-database tags can be deleted.');
  
  $object = _create_object(TEST_CLASS);
  $object->addTag('toto, tutu, test');
  $object->save();
  $id = $object->id;
  $object = Doctrine::getTable(TEST_CLASS)->find($id);
  
  $object2 = _create_object(TEST_CLASS);
  $object2->addTag('clever age, symfony, test');
  $object2->save();
  $object2->removeTag('test');
  $object2->save();
  $t->ok(!$object2->hasTag('test'), 'we can remove tag to saved objects');
  
  $object_tags = $object->getTags();
  $object2_tags = $object2->getTags();
  $t->ok((count($object2_tags) == 2) && (count($object_tags) == 3), 'removing one tag as no effect on the other tags of the object, neither on the other objects.');
  
  $object2_tags = $object2->getTags(array('serialized' => true));
  $t->is($object2_tags, 'clever age, symfony', 'tags can be retrieved in a serialized form.');
  
  $object->removeAllTags();
  $object->setTags('');
  $object->save();
  $id = $object->id;
  $object = Doctrine::getTable(TEST_CLASS)->find($id);
  $t->ok(count($object->getTags()) == 0, 'when the tags are set twice, or all removed twice, before the object is saved, then all the prvious tags are still removed.');
  
  unset($object, $object2, $object2_copy);


// these tests check the various methods for applying tags to an object
$t->diag('various methods for applying tags');
  $object = _create_object(TEST_CLASS);
  $object->addTag('toto');
  $object_tags = $object->getTags();
  $t->ok((count($object_tags) == 1) && ($object_tags['toto'] == 'toto'), 'one tag can be added alone.');
  
  $object->addTag('titi,tutu');
  $object_tags = $object->getTags();
  $t->ok((count($object_tags) == 3) && $object->hasTag('tutu') && $object->hasTag('titi'), 'tags can be added with a comma-separated string.');
  $t->ok($object->hasTag('titi, tutu'), 'comma-separated strings are divided into several tags.');
  
  $object = _create_object(TEST_CLASS);
  $object->addTag('titi
  tutu');
  $object_tags = $object->getTags();
  $t->ok((count($object_tags) == 2) && $object->hasTag('titi') && $object->hasTag('tutu'), 'tags can be added using line breaks as separators.');
  $t->ok($object->hasTag('titi
  tutu'), 'line-breaks-separated strings are divided into several tags.');
  
  $object = _create_object(TEST_CLASS);
  $object->addTag('titi
  
  tutu');
  $object_tags = $object->getTags();
  $t->ok((count($object_tags) == 2) && $object->hasTag('titi') && $object->hasTag('tutu'), 'when adding tags using line breaks as separators, remove blank lines.');
  
  $object = _create_object(TEST_CLASS);
  $object->addTag(array('titi', 'tutu'));
  $object_tags = $object->getTags();
  $t->ok((count($object_tags) == 2) && $object->hasTag('tutu') && $object->hasTag('titi'), 'tags can be added with an array.');
  
  $object->setTags('wallace, gromit');
  $object_tags = $object->getTags();
  $t->ok((count($object_tags) == 2) && $object->hasTag('wallace') && $object->hasTag('gromit'), 'tags can be set directly using setTags().');
  
  unset($object);


// these tests check for the tagging removal on object deletion

// clean the database
Doctrine::getTable('Tagging')->findAll()->delete();
Doctrine::getTable('Tag')->findAll()->delete();
Doctrine::getTable(TEST_CLASS)->findAll()->delete();

$t->diag('taggings removal on objects deletion');
  $object1 = _create_object(TEST_CLASS);
  $object1->addTag('tag2,tag3,tag1,tag4,tag5,tag6');
  $object1->save();
  
  $object2 = _create_object(TEST_CLASS);
  $object2->addTag('tag4,tag7,tag8');
  $object2->save();
  
  $object2->delete();
  
  $tags = PluginTagTable::getAllTagNameWithCount();
  $t->ok(isset($tags['tag4']) && !isset($tags['tag7']), 'the taggings associated to one object are deleted when this object is deleted.');

// these tests check for PluginTagTable methods (tag clouds generation)

// clean the database
Doctrine::getTable('Tagging')->findAll()->delete();
Doctrine::getTable('Tag')->findAll()->delete();
Doctrine::getTable(TEST_CLASS)->findAll()->delete();

$t->diag('tag clouds');
  $object1 = _create_object(TEST_CLASS);
  $object1->addTag('tag2,tag3,tag1,tag4,tag5,tag6');
  $object1->save();
  
  $object2 = _create_object(TEST_CLASS);
  $object2->addTag('tag1,tag3,tag4,tag7');
  $object2->save();
  
  $object3 = _create_object(TEST_CLASS);
  $object3->addTag('tag2,tag3,tag7,tag8');
  $object3->save();
  
  $object4 = _create_object(TEST_CLASS);
  $object4->addTag('tag3');
  $object4->save();
  
  $object5 = _create_object(TEST_CLASS);
  $object5->addTag('tag1,tag3,tag7');
  $object5->save();
  
  // getAll() test
  $tags = PluginTagTable::getAllTagName();
  $t->is($tags, array('tag1', 'tag2', 'tag3', 'tag4', 'tag5', 'tag6', 'tag7', 'tag8'), 'all tags can be retrieved with getAllTagName().');
  
  // getAllWithCount() test
  $tags = PluginTagTable::getAllTagNameWithCount();
  $t->is($tags, array('tag1' => 3, 'tag2' => 2, 'tag3' => 5, 'tag4' => 2, 'tag5' => 1, 'tag6' => 1, 'tag7' => 3, 'tag8' => 1), 'all tags can be retrieved and counted with getAllTagNameWithCount().');
  
  $tags = PluginTagTable::getAllTagNameWithCount(null, array('min_tags_count' => 2));
  $t->is($tags, array('tag1' => 3, 'tag2' => 2, 'tag3' => 5, 'tag4' => 2, 'tag7' => 3), 'tags can be retrieved and counted with getAllTagNameWithCount() according to the min occurencies');
  
  // getPopulars() test
  $q = new Doctrine_Query();
  $q->limit(3); 
  $tags = PluginTagTable::getPopulars($q);
  $t->is(array_keys($tags), array('tag1', 'tag3', 'tag7'), 'most popular tags can be retrieved with getPopulars().');
  $t->ok($tags['tag3'] >= $tags['tag1'], 'getPopulars() preserves tag importance.');
  
  // getRelatedTags() test
  $tags = PluginTagTable::getRelatedTags('tag8');
  $t->is(array_keys($tags), array('tag2', 'tag3', 'tag7'), 'related tags can be retrieved with getRelatedTags().');
  
  $tags = PluginTagTable::getRelatedTags('tag2', array('limit' => 1));
  $t->is(array_keys($tags), array('tag7'), 'when a limit is set, only most popular related tags are returned by getRelatedTags().');
  
  // getRelatedTags() test
  $tags = PluginTagTable::getRelatedTags('tag7');
  $t->is(array_keys($tags), array('tag1', 'tag2', 'tag3', 'tag4', 'tag8'), 'getRelatedTags() aggregates tags from different objects.');
  
  // getRelatedTags() test
  $tags = PluginTagTable::getRelatedTags(array('tag2', 'tag7'));
  $t->is(array_keys($tags), array('tag3', 'tag8'), 'getRelatedTags() can retrieve tags related to an array of tags.');
  
  // getRelatedTags() test
  $tags = PluginTagTable::getRelatedTags('tag2,tag7');
  $t->is(array_keys($tags), array('tag3', 'tag8'), 'getRelatedTags() also accepts a coma-separated string.');
  
  // getObjectTaggedWith() tests
  $object_2_1 = _create_object(TEST_CLASS_2);
  $object_2_1->addTag('tag1,tag3,tag7');
  $object_2_1->save();
  
  $object_2_2 = _create_object(TEST_CLASS_2);
  $object_2_2->addTag('tag2,tag7');
  $object_2_2->save();
  
  $tagged_with_tag4 = PluginTagTable::getObjectTaggedWith('tag4');
  $t->ok(count($tagged_with_tag4) == 2, 'getObjectTaggedWith() returns objects tagged with one specific tag.');
  
  $tagged_with_tag7 = PluginTagTable::getObjectTaggedWith('tag7');
  $t->ok(count($tagged_with_tag7) == 5, 'getObjectTaggedWith() can return several object types.');
  
  $tagged_with_tag17 = PluginTagTable::getObjectTaggedWith(array('tag1', 'tag7'));
  $t->ok(count($tagged_with_tag17) == 3, 'getObjectTaggedWith() returns objects tagged with several specific tags.');
  
  $tagged_with_tag127 = PluginTagTable::getObjectTaggedWith('tag1, tag2, tag7',
                                               array('nb_common_tags' => 2));
  $t->ok(count($tagged_with_tag127) == 6, 'the "nb_common_tags" option of getObjectTaggedWith() returns objects tagged with a certain number of tags within a set of specific tags.');
  
  // PluginTagTable::getObjectTaggedWithQuery()
  $test_class_object_query = PluginTagTable::getObjectTaggedWithQuery(TEST_CLASS, 'tag1');
  $t->is(get_class($test_class_object_query), 'Doctrine_Query', 'PluginTagTable::getObjectTaggedWithQuery() return a Doctrine_Query object');
  $rs = $test_class_object_query->limit(1)->execute(array())->getFirst();
  $t->is(get_class($rs), TEST_CLASS, 'PluginTagTable::getObjectTaggedWithQuery() can be executed and return the ' . TEST_CLASS . ' object');
  
  
  Doctrine::getTable('Tagging')->findAll()->delete();
  Doctrine::getTable('Tag')->findAll()->delete();
  Doctrine::getTable(TEST_CLASS)->findAll()->delete();


// these tests check for the application of triple tags

$t->diag('applying triple tagging');

  $object = _create_object(TEST_CLASS);
  $object->addTag('tutu');
  $object->save();
  
  $object = _create_object(TEST_CLASS);
  $object->addTag('ns:key=value');
  $object->addTag('ns:key=tutu');
  $object->addTag('ns:key=titi');
  $object->addTag('ns:key=toto');
  $object->save();
  
  $object_tags = $object->getTags();
  $t->ok($object->hasTag('ns:key=value'), 'object has triple tag');
  
  $tag = PluginTagTable::findOrCreateByTagname('ns:key=value');
  $t->ok($tag->getIsTriple(), 'a triple tag created from a string is identified as a triple.');
  
  $tag = PluginTagTable::findOrCreateByTagname('tutu');
  $t->ok(!$tag->getIsTriple(), 'a non tripled tag created from a string is not identified as a triple.');


// these tests check the retrieval of the tags of one object, based on
// triple-tags constraints

$t->diag('retrieving triple tags, and extracting only parts of it');

  $object = _create_object(TEST_CLASS);
  $object->addTag('geo:lat=50.7');
  $object->addTag('geo:long=6.1');
  $object->addTag('de:city=Aachen');
  $object->addTag('fr:city=Aix la Chapelle');
  $object->addTag('en:city=Aix Chapel');
  $object->save();
  
  // get all the tags
  $tags = $object->getTags();
  $t->ok(count($tags) == 5, 'The addTags() method permits to create triple tags, that can be retrieved using getTags().');
  
  $id = $object->id;
  $object = Doctrine::getTable(TEST_CLASS)->find($id);
  $tags = $object->getTags();
  $t->ok(count($tags) == 5, 'The addTags() method permits to create triple tags, that can be retrieved using getTags(), even when saved.');
  
  // get all the informations in the "geo" namespace
  $tags = $object->getTags(array('is_triple' => true,
                                 'namespace' => 'geo',
                                 'return'    => 'value'));
  $t->ok(count($tags) == 2, 'The getTags() method permits to select triple tags in one specific namespace.');
  
  // get all the values of the triple tags for which the key is "city", whatever
  // the namespace
  $tags = $object->getTags(array('is_triple' => true,
                                 'key'       => 'city',
                                 'return'    => 'value'));
  $t->ok(count($tags) == 3, 'The getTags() method permits to select triple tags for one specific key.');
  
  $object2 = _create_object(TEST_CLASS);
  $object2->addTag('geo:lat=48.8');
  $object2->addTag('geo:long=2.4');
  $object2->addTag('de:city=Paris');
  $object2->addTag('fr:city=Paris');
  $object2->addTag('en:city=Paris');
  $object2->save();
  
  // get all the values of the triple tags for which the key is "city", whatever
  // the namespace
  $tags = $object2->getTags(array('is_triple' => true,
                                  'key'       => 'city',
                                  'return'    => 'value'));
  $t->ok(count($tags) == 1, 'When selecting only the values of triple tags of one object, there is no duplicate.');
  
  $ns = $object2->getTags(array('is_triple' => true,
                                'return'    => 'namespace'));
  $t->ok(count($ns) == 4, 'The method getTags() permit to select only the names of the namespaces of the tags attached to one object.');


// these tests check for PluginTagTable triple tags specific methods (tag clouds generation)
sfConfig::set('app_sfPropelActAsTaggableBehaviorPlugin_triple_distinct', false);
$t->diag('querying triple tagging');

  $tags_triple = PluginTagTable::getAllTagName(null, array('triple' => true));
  
  $t->ok(in_array('ns:key=value', $tags_triple), 'triple tags are returned when searching for triples only.');
  $t->ok(!in_array('tutu', $tags_triple), 'ordinary tags are not returned when searching for triples only.');
  
  $tags_triple = PluginTagTable::getAllTagName(null, array('triple' => false));
  
  $t->ok(in_array('tutu', $tags_triple), 'normal tags are returned when searching for ordinary ones only.');
  $t->ok(!in_array('ns:key=value', $tags_triple), 'triple tags are not returned when searching for normal ones.');

// these tests the search of specific triple tags parts

$t->diag('searching for specific parts of triple');
  $tags_triple = PluginTagTable::getAllTagName(null, array('triple' => true, 'namespace' => 'ns'));
  
  $t->ok($tags_triple === array('ns:key=value', 'ns:key=tutu', 'ns:key=titi', 'ns:key=toto'), 'it is possible to search for triple tags by namespace.');
  
  $tags_triple = PluginTagTable::getAllTagName(null, array('triple' => true, 'key' => 'key'));
  
  $t->ok($tags_triple === array('ns:key=value', 'ns:key=tutu', 'ns:key=titi', 'ns:key=toto'), 'it is possible to search for triple tags by key.');
  
  $tags_triple = PluginTagTable::getAllTagName(null, array('triple' => true, 'value' => 'tutu'));
  
  $t->ok($tags_triple === array('ns:key=tutu'), 'it is possible to search for triple tags by value.');
  
  $objects_triple = PluginTagTable::getObjectTaggedWith(array(), array('namespace' => 'ns', 'model' => TEST_CLASS));
  $t->ok(count($objects_triple) == 1, 'it is possible to retrieve objects tagged with certain triple tags.');


// these tests check for the behavior of the triple tags when the plugin is set
// up so that namespace:key is a unique key

// clean the database
Doctrine::getTable('Tagging')->findAll()->delete();
Doctrine::getTable('Tag')->findAll()->delete();
Doctrine::getTable(TEST_CLASS)->findAll()->delete();

sfConfig::set('app_sfDoctrineActAsTaggablePlugin_triple_distinct', true);

$t->diag('querying triple tagging');

  $object = _create_object(TEST_CLASS);
  $object->addTag('tutu');
  $object->save();
  
  $object = _create_object(TEST_CLASS);
  $object->addTag('ns:key=value');
  $object->addTag('ns:key=tutu');
  $object->addTag('ns:key=titi');
  $object->addTag('ns:second_key=toto');
  $object->save();
  
  $tags_triple = PluginTagTable::getAllTagName(null, array('triple' => true, 'namespace' => 'ns'));
  
  $t->ok(count($tags_triple) == 2, 'it is possible to set up the plugin so that namespace:key is a unique key.');
  
  $object2 = _create_object(TEST_CLASS);
  $object2->addTag('ns:key=value');
  $object2->addTag('ns:second_key=toto');
  $object2->save();
  
  $tags_triple = PluginTagTable::getAllTagName(null, array('triple' => true, 'namespace' => 'ns'));
  $t->ok(count($tags_triple) == 3, 'it is possible to apply triple tags to various objects when the plugin is set up so that namespace:key is a unique key.');


// clean the database
Doctrine::getTable('Tagging')->findAll()->delete();
Doctrine::getTable('Tag')->findAll()->delete();
Doctrine::getTable(TEST_CLASS)->findAll()->delete();

// Test ability to use different options for tag
// - change case type
// - use personnalised separator
$obj = _create_object(TEST_CLASS);
$obj->addTag('loutre, MonSieur', array('case' => 'strtolower'));
$t->is($obj->getTags(), array('monsieur' => 'monsieur', 'loutre' => 'loutre'), 'We can specify PHP methods to change the tag case');

unset($obj);

/*
 * Klemens Ullmann 2008-12-03: deactivated, since there is no code to handle it
 *
$obj = _create_object(TEST_CLASS);
$obj->addTag('loutre aime, monsieur', array('separator' => array(' ', ',')));
$t->is($obj->getTags(), array('monsieur' => 'monsieur', 'aime' => 'aime', 'loutre' => 'loutre'), 'We can specify how to separate tags');
*/

function _create_object($name)
{
    $classname = $name;

    if (!class_exists($classname))
    {
        throw new Exception(sprintf('Unknow class "%s"', $classname));
    }

    $class = new $classname();

    if($class->getTable()->hasColumn('label'))
    {
        $class->label = 2;
    }

    return $class;
}