<?php echo '<?xml version="1.0" encoding="utf-8"?>
'?>
<feed xmlns="http://www.w3.org/2005/Atom">	
<?php $partUrl =  (@$_SERVER['HTTPS'] ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] ;?>
<?php $selfUrl = $partUrl . $_SERVER['REQUEST_URI']; ?>
        <title>SpotWeb Spot overzicht</title>
        <link href="<?php echo $selfUrl ?>" rel="self" />
        <link href="<?php echo str_replace('page=atom','page=index', $selfUrl); ?>" rel="alternate" />
        <id><?php echo $selfUrl?></id>
        <updated><?php echo date('c')?></updated>
        <generator>Spotweb</generator>
        <icon><?echo $partUrl?>/images/touch-icon-iphone4.png</icon>

<?php foreach($spots as $spot):
$spotLink = $partUrl . "?page=getspot&amp;messageid=" . urlencode($spot['messageid']); 
$id = "tag:$_SERVER[HTTP_HOST],2011:spot/". urlencode($spot['messageid']);
$spot['description'] = @$tplHelper->formatDescription($spot['description']); ?>
        <entry>
                <title><?php echo htmlspecialchars($spot['title']) ?></title>
                <author><name><?php echo htmlspecialchars($spot['poster'])?></name></author>
                <link rel="alternate" type="text/html" href="<?php echo $spotLink ?>"/>
                <link rel="alternate" type="application/x-nzb" href="<?php echo $partUrl . "?page=getnzb&amp;messageid=". urlencode($spot['messageid'])?>" title="NZB"/>
                <link rel="related" type="text/html" href="<?php echo htmlspecialchars($spot['website'])?>" />
                <id><?php echo $id ?></id>
                <published><? echo date('c', $spot['stamp'])?></published>
                <category label="<?echo SpotCategories::HeadCat2Desc($spot['category'])?>" term="cat<?echo $spot['category']?>"/>
                <category label="<?echo SpotCategories::Cat2ShortDesc($spot['category'],$spot['subcat'])?>" term="<?echo SpotCategories::SubcatToFilter($spot['category'],$spot['subcat'])?>"/>
                <content type="html"><![CDATA[<?php echo $spot['description']?><br/><img src="<?php echo $spot['image'] ?>"/>]]></content>
        </entry>
<?php endforeach; ?>

</feed>