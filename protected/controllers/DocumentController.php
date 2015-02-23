<?php

class DocumentController extends CoreController
{
    public $moduleName = 'Document';

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($id),
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$model=new Document;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Document']))
		{
			$model->attributes=$_POST['Document'];
			if($model->save())
            {
                Yii::app()->user->setFlash('success', 'Document "'. $model->title .'" saved!');
                $model=new Document;
            }
		}

		$this->render('create',array(
			'model'=>$model,
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$model=$this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Document']))
		{
			$model->attributes=$_POST['Document'];
			if($model->save())
            {
                Yii::app()->user->setFlash('success', 'Document "'. $model->title .'" updated!');
                $model=$this->loadModel($id);
            }
		}

		$this->render('update',array(
			'model'=>$model,
		));
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		$model = $this->loadModel($id);
        
        if($model->delete())
        {
            Yii::app()->user->setFlash('success', 'Document "'. $model->title .'" deleted!');
        }
         
		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex()
	{
		$this->redirect(array('document/admin'));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$model=new Document('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['Document']))
			$model->attributes=$_GET['Document'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer the ID of the model to be loaded
	 */
	public function loadModel($id)
	{
		$model=Document::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='document-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
    
    public function actionAddFromXML()
    {
        $model = new FormDocument();
        
        if(isset($_POST['FormDocument']))
        {
            $model->attributes = $_POST['FormDocument'];
            $file = CUploadedFile::getInstance($model, 'file');
            
            if($model->validate())
            {
                if($file != null)
                {
                    // simpan file
                    $filePath = Yii::app()->basePath . '/../files/corpus/korpus.xml';
                    $file->saveAs($filePath);
                    
                    // buka file
                    $handle = simplexml_load_file($filePath);
                    
                    $models = Document::model();
                    $tr = $models->dbConnection->beginTransaction();
                    
                    Document::model()->deleteAll();
        
                    try
                    {
                        $counter = 0;
                        
                        foreach($handle->document as $doc)
                        {
                            // simpan objeck dokumen
                            $document = new Document();
                            $document->author = trim($doc->author);
                            $document->content = trim($doc->content);
                            $document->published = trim($doc->published);
                            $document->title = trim($doc->title);
                            $document->url = trim($doc->url);
                            
                            $document->save();
                        }
                        
                        Yii::app()->user->setFlash('success', 'Documents saved!');
                        
                        $tr->commit();
                    }
                    catch(Exception $e)
                    {
                        $tr->rollback();
                        Yii::app()->user->setFlash('error', 'Error, please try again');
                    }
                }
                else
                {
                     Yii::app()->user->setFlash('error', 'Error, please try again');
                }
            }
        }
                            
        $this->render('addFromXML', array(
            'model' => $model,
        ));
    }
    
    public function actionDoIndex()
    {
        $document = Document::model()->findAll();
        
        Yii::import('application.vendor.*');
        require_once('Zend/Search/Lucene.php');
        
        $index = new Zend_Search_Lucene(Yii::getPathOfAlias('application.index'), true);
        
        try
        {
            foreach($document as $doc)
            {
                $indexDoc = new Zend_Search_Lucene_Document();
                
                $indexDoc->addField(Zend_Search_Lucene_Field::text("title", CHtml::encode($doc->title), 'utf-8'));
                $indexDoc->addField(Zend_Search_Lucene_Field::text("content", CHtml::encode($doc->content), 'utf-8'));
                $indexDoc->addField(Zend_Search_Lucene_Field::text("author", CHtml::encode($doc->author), 'utf-8'));
                $indexDoc->addField(Zend_Search_Lucene_Field::unIndexed("id", CHtml::encode($doc->id), 'utf-8'));
                $indexDoc->addField(Zend_Search_Lucene_Field::keyword("url", CHtml::encode($doc->url), 'utf-8'));
                
                $index->addDocument($indexDoc);
            }
            
            $index->commit();
            
            Yii::app()->user->setFlash('success', 'Indexing success!');
        }
        catch(Exception $e)
        {
            Yii::app()->user->setFlash('error', 'Indexing fail, try again!');
        }
        
        $this->redirect(array('document/admin'));
    }
}