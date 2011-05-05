<?php echo '<?xml version="1.0" encoding="utf-8"?>
'?>
<feed xmlns="http://www.w3.org/2005/Atom">	
<?php 
$baseUrl = htmlspecialchars($tplHelper->makeBaseUrl("full"));
$selfUrl = htmlspecialchars($tplHelper->makeSelfUrl("full"));
$indexUrl = htmlspecialchars($tplHelper->getPageUrl('index', true));
?>
        <title>SpotWeb Spot overzicht</title>
        <link href="<?php echo $selfUrl ?>" rel="self" />
        <link href="<?php echo $indexUrl ?>" rel="alternate" />
        <id><?php echo $selfUrl?></id>
        <updated><?php echo date('c')?></updated>
        <generator>Spotweb</generator>
        <icon><?php echo $baseUrl?>images/touch-icon-iphone4.png</icon>

<?php foreach($spots as $spot):
$spotLink = $tplHelper->makeSpotUrl($spot);
$id = 'tag:' . $spot['tag'] . ',2011:spot/'. urlencode($spot['messageid']);
$spot['description'] = @$tplHelper->formatContent($spot['description']); ?>
        <entry>
                <title><?php echo htmlspecialchars($spot['title']) ?></title>
                <author><name><?php echo htmlspecialchars($spot['poster'])?></name></author>
                <link rel="alternate" type="text/html" href="<?php echo $spotLink ?>"/>
                <link rel="alternate" type="application/x-nzb" href="<?php echo $tplHelper->makeNzbUrl($spot)?>" title="NZB"/>
                <link rel="related" type="text/html" href="<?php echo htmlspecialchars($spot['website'])?>" />
                <id><?php echo $id ?></id>
                <published><?php echo date('c', $spot['stamp'])?></published>
                <updated><?php echo date('c', $spot['stamp'])?></updated>
                <category label="<?php echo SpotCategories::HeadCat2Desc($spot['category'])?>" term="cat<?php echo $spot['category']?>"/>
                <category label="<?php echo SpotCategories::Cat2ShortDesc($spot['category'],$spot['subcat'])?>" term="<?php echo SpotCategories::SubcatToFilter($spot['category'],$spot['subcat'])?>"/>
                <content type="html"><![CDATA[<?php echo $spot['description']?><br/><img src="<?php echo $spot['image'] ?>"/>]]></content>
        </entry>
<?php endforeach; ?>

</feed>