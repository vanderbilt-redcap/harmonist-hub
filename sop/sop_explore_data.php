<div class="container">
    <div class="optionSelect">
        <h3>Explore Data</h3>
        <p class="hub-title"></p>
    </div>
    <div>
        <div class="panel-body">
            <iframe class="commentsform" id="explore-dab" src="https://bit.ly/testshiny" style="border: none;height: 860px;width: 100%;"></iframe>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('#explore-dab').attr('src', 'https://iedeaharmonist.app.vumc.org/dab/?tokendab=' + <?=json_encode(html_entities($_REQUEST['tokendab'],ENT_QUOTES))?>);
    });
</script>