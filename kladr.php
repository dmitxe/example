<?php
use yii\helpers\Html;
use yii\jui\DatePicker;
/**
 * @var \yii\web\View $this
 * @var \app\models\Businessman $model
 */
if ($model->add_codes) {
    $modelsCodes = $model->getAddCodes();
} else {
    $modelsCodes = [];
}

$this->title = 'Заполнение данных предпринимателя';
$this->params['breadcrumbs'][] = $this->title;
?>
<h1>service/open</h1>

<p>
    <?php $form = \yii\bootstrap\ActiveForm::begin([
        'id' => 'registration-form',
        'enableAjaxValidation' => false,
        'enableClientValidation' => true,
    ]); ?>

    <?= $form->errorSummary($model) ?>

<div class="form-group">
    <label class="control-label">Тип файла</label>
    <?= Html::dropDownList('type_output', 1, [1 => 'Xls', 2=> 'Tcpdf', 3 => 'MPdf', 4 => 'Dompdf', 5 => 'HTML'], ['class' => 'form-control']) ?>
</div>

<?= $form->field($model, 'firstname') ?>

<?= $form->field($model, 'middlename') ?>

<?= $form->field($model, 'lastname') ?>

<?= $form->field($model, 'inn') ?>

<?= $form->field($model, 'sex')->dropDownList([1 => 'Мужской', 2=> 'Женский']) ?>

<?= $form->field($model, 'birthday')->widget(\yii\jui\DatePicker::class, [
    'language' => 'ru',
    'dateFormat' => 'yyyy-MM-dd',
    'options' => [
        'class' => 'form-control',
    ]
]) ?>

<?= $form->field($model, 'birthcity') ?>

<?= $form->field($model, 'address')->hiddenInput()->label(false) ?>

<div class="form-group">
    <label>Выберите адрес</label>
    <?= Html::textInput('kladr', null, ['id' => 'kladr', 'class' => 'form-control']) ?>
</div>

<?= $form->field($model, 'zip_code') ?>

<?= $form->field($model, 'region_code') ?>

<?= $form->field($model, 'area') ?>

<?= $form->field($model, 'area_title') ?>

<?= $form->field($model, 'city') ?>

<?= $form->field($model, 'city_title') ?>

<?= $form->field($model, 'locality') ?>

<?= $form->field($model, 'locality_title') ?>

<?= $form->field($model, 'street') ?>

<?= $form->field($model, 'street_title') ?>

<?= $form->field($model, 'house') ?>

<?= $form->field($model, 'house_number') ?>

<?= $form->field($model, 'house_housing') ?>

<?= $form->field($model, 'house_housing_number') ?>

<?= $form->field($model, 'apartment') ?>

<?= $form->field($model, 'apartment_number') ?>

<?= $form->field($model, 'passport') ?>

<?= $form->field($model, 'passport_date')->widget(\yii\jui\DatePicker::class, [
    'language' => 'ru',
    'dateFormat' => 'yyyy-MM-dd',
    'options' => [
        'class' => 'form-control',
    ]
]) ?>

<?= $form->field($model, 'passport_issued') ?>

<?= $form->field($model, 'passport_code') ?>

<?= $form->field($model, 'give_document')->dropDownList([
    1 => 'выдать заявителю',
    2 => 'выдать заявителю или лицу, действующему на основании доверенности',
    3 => 'направить по почте',
]) ?>

<?= $form->field($model, 'email') ?>

<?= $form->field($model, 'phone') ?>

<fieldset>
    <legend>Код основного вида деятельности</legend>
    <?= $this->render('_add_okved', ['model' => $model->okved, 'nom' => 0]) ?>
</fieldset>
<fieldset>
    <legend>Коды дополнительных видов деятельности</legend>
    <div id="activity_code_panel">
        <?php foreach ($modelsCodes as $i=>$modelCode) {
            echo $this->render('_add_okved', ['model' => $modelCode, 'nom' => $i+1]);
        } ?>
    </div>
    <div class="form-group">
        <?= Html::button('Добавить дополнительный вид деятельности', ['class' => 'btn btn-lg btn-primary', 'onclick' => 'add_code()']) ?>
        <?= Html::button('Удалить все доп. виды деятельности', ['class' => 'btn btn-lg btn-danger', 'onclick' => 'clear_codes()']) ?>
    </div>
</fieldset>


<?= Html::submitButton(Yii::t('app', 'Получить документ'), ['class' => 'btn btn-success btn-block']) ?>

    <?php \yii\bootstrap\ActiveForm::end(); ?>

<script type="text/javascript">
    var max_code = <?= count($modelsCodes) ?>;
    function select_section(nom) {
        $.getJSON('/okved/get-category?section_id='+$('#section_id_'+nom).val(), function(data) {
            $('#category_id_'+nom).empty().append('<option>Выберите категорию</option>');
            $('#activity_id_'+nom).empty().append('<option>Выберите вид деятельности</option>');
            $.each(data, function(key, value) {
                $('#category_id_'+nom).append('<option value="'+key+'">'+value+'</option>');
            })
        })
    }
    function select_category(nom) {
        $.getJSON('/okved/get-activity?category_id='+$('#category_id_'+nom).val(), function(data) {
            $('#activity_id_'+nom).empty().append('<option>Выберите вид деятельности</option>');
            $.each(data, function(key, value) {
                $('#activity_id_'+nom).append('<option value="'+key+'">'+value+'</option>');
            })
        })
    }

    function add_code() {
        if (max_code<56) {
            max_code++;
            $.ajax({
                url: "<?= \yii\helpers\Url::to(['service/add-code']) ?>?nom="+max_code,
                success: function(data){
                    $('#activity_code_panel').append(data);
                }
            });
        } else {
            alert('Вы не можете добавить больше 56 дополнительных кодов!');
        }
    }

    function clear_codes() {
        $('#activity_code_panel').empty();
    }

    function get_kladr(data) {
        console.log(data);
        if (data) {
            var items = {'building':null, 'street':null, 'city':null, 'district': null, 'region': null, 'cityOwner': 'cityOwner' };
            items[data.contentType] = {'contentType': data.contentType, 'name': data.name, 'id': data.id, 'okato': data.okato,
                'type': data.type, 'typeShort': data.typeShort, 'zip': data.zip };
            for (i=0; i<data.parents.length; i++) {
                items[data.parents[i].contentType] = data.parents[i];
            }
            $('#businessman-zip_code').val('');
            $('#businessman-street').val('');
            $('#businessman-street_title').val('');
            $('#businessman-region_code').val('');
            $('#businessman-area').val('');
            $('#businessman-area_title').val('');
            $('#businessman-city').val('');
            $('#businessman-city_title').val('');
            $('#businessman-locality').val('');
            $('#businessman-locality_title').val('');
            $('#businessman-house').val('');
            $('#businessman-house_number').val('');

            $('#businessman-address').val(JSON.stringify(data));
            // обработка почтового кода
            if (items['building']) {
                $('#businessman-zip_code').val(items['building'].zip);
                $('#businessman-house').val(items['building'].typeShort);
                $('#businessman-house_number').val(items['building'].name);
            } else if (items['street']){
                if (items['street'].zip) {
                    $('#businessman-zip_code').val(items['street'].zip);
                } else {
                    $('#businessman-zip_code').val(items['city'].zip);
                }
            }
            if (items['street']){
                $('#businessman-street').val(items['street'].typeShort);
                $('#businessman-street_title').val(items['street'].name);
            }
            if (items['region']){
                $('#businessman-region_code').val(items['region'].id.substring(0,2))
            }
            if (items['district']){
                $('#businessman-area').val(items['district'].typeShort);
                $('#businessman-area_title').val(items['district'].name);
            }
            if (items['city']){
                if (items['city'].typeShort == 'г') {
                    $('#businessman-city').val(items['city'].typeShort);
                    $('#businessman-city_title').val(items['city'].name);
                } else {
                    $('#businessman-locality').val(items['city'].typeShort);
                    $('#businessman-locality_title').val(items['city'].name);
                }
            }
        }
    }

    $(document).ready(function() {
        $('#kladr').kladr({
            oneString: true,
            change: function (obj) {
                get_kladr(obj);
            }
        });
    });
</script>