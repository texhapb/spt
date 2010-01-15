<?php
	mb_internal_encoding('UTF-8');
	$xsl_fb2_2_txt = <<<XSL_FB2_2_TXT
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:fb="http://www.gribuser.ru/xml/fictionbook/2.0">
	<xsl:strip-space elements="*"/>
	<xsl:output method="text" encoding="utf-8"/>
	<xsl:key name="note-link" match="fb:section|fb:p" use="@id"/>

	<xsl:template match="*">
<!-- <xsl:variable/> -->

				<!-- BUILD BOOK -->
<xsl:for-each select="fb:body">
<!-- <xsl:apply-templates /> -->
<xsl:apply-templates/>
<xsl:if test="position()!=1">
<xsl:text >&#010;&#010;</xsl:text>
</xsl:if>

</xsl:for-each>
<xsl:text >&#010;</xsl:text>
	</xsl:template>

<!-- body -->
<xsl:template match="fb:body">
<xsl:text >&#010;</xsl:text>
<xsl:apply-templates/>
</xsl:template>

<xsl:template match="fb:section">
<xsl:text >&#010;</xsl:text>
<xsl:apply-templates select="./*"/>
</xsl:template>
	
	
<!-- section/title -->
<xsl:template match="fb:title">
<xsl:text >&#010;&#010;</xsl:text>
<xsl:apply-templates/>
<xsl:text >&#010;</xsl:text>
</xsl:template>

	<!-- subtitle -->
<xsl:template match="fb:subtitle">
<xsl:text >&#010;</xsl:text>
<xsl:apply-templates/>
<xsl:text >&#010;&#010;</xsl:text>
</xsl:template>

<!-- p -->
<xsl:template match="fb:p">
<xsl:apply-templates/>
<xsl:text >&#010;&#010;</xsl:text>
</xsl:template>

<xsl:template match="fb:p" mode="note">
<xsl:apply-templates/>
</xsl:template>

<xsl:template match="fb:title" mode="note">
<xsl:apply-templates mode="note"/><xsl:text disable-output-escaping="yes"> - </xsl:text>
</xsl:template>


<xsl:template match="fb:strong|fb:emphasis|fb:style"><xsl:apply-templates/></xsl:template>

<xsl:template match="fb:a">
<xsl:choose>
<xsl:when test="(@type) = 'note'">
<xsl:choose>
<xsl:when test="starts-with(@xlink:href,'#')"><xsl:for-each select="key('note-link',substring-after(@xlink:href,'#'))">[<xsl:apply-templates mode="note"/>]</xsl:for-each></xsl:when>
<xsl:otherwise><xsl:for-each select="key('note-link',@xlink:href)">[<xsl:apply-templates mode="note"/>]</xsl:for-each></xsl:otherwise>
</xsl:choose>
</xsl:when>
<xsl:otherwise>
<xsl:apply-templates/>
</xsl:otherwise>
</xsl:choose>
</xsl:template>

<xsl:template match="fb:empty-line">
<xsl:text >&#010;&#010;</xsl:text>
</xsl:template>

<!-- annotation -->
<xsl:template name="annotation">
<xsl:apply-templates/>
<xsl:text >&#010;</xsl:text>
</xsl:template>

<!-- epigraph -->
<xsl:template match="fb:epigraph">
<xsl:apply-templates/>
<xsl:text >&#010;</xsl:text>
</xsl:template>

<!-- cite -->
<xsl:template match="fb:cite">
	<xsl:text >&#010;&#010;</xsl:text>
	<xsl:apply-templates/>
	<xsl:text >&#010;</xsl:text>
</xsl:template>


	<!-- cite/text-author -->
<xsl:template match="fb:text-author">
	<xsl:text >&#160;&#160;&#160;&#160;</xsl:text>
	<xsl:apply-templates/>
	<xsl:text >&#010;</xsl:text>
</xsl:template>
	<!-- date -->
<xsl:template match="fb:date">
	<xsl:text >&#160;&#160;&#160;&#160;</xsl:text>
	<xsl:apply-templates/>
	<xsl:text >&#010;</xsl:text>
</xsl:template>

<xsl:template match="fb:poem">
<xsl:apply-templates/>
</xsl:template>

	<!-- stanza -->
<xsl:template match="fb:stanza">
<xsl:apply-templates/>
<xsl:text >&#010;</xsl:text>
</xsl:template>
	<!-- v -->
<xsl:template match="fb:v">
<xsl:text >		</xsl:text>
<xsl:apply-templates/>
<xsl:text >&#010;</xsl:text>
</xsl:template>

</xsl:stylesheet>
XSL_FB2_2_TXT;

	$doc = new DOMDocument();
	$xsl = new XSLTProcessor();

	$doc->loadXML($xsl_fb2_2_txt);
	$xsl->importStyleSheet($doc);

	$doc->load($argv[1]);
	echo $xsl->transformToXML($doc);

?>