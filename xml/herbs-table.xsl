<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html" encoding="UTF-8" indent="yes"/>
    <xsl:param name="sortField" select="'price'"/>
    <xsl:param name="sortOrder" select="'ascending'"/>
    <xsl:param name="numericSort" select="'false'"/>

    <xsl:template match="/">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-light">
            <tr>
                <th scope="col">ID</th>
                <th scope="col">名称</th>
                <th scope="col">类别</th>
                <th scope="col">价格</th>
                <th scope="col">库存</th>
                <th scope="col">产地</th>
            </tr>
            </thead>
            <tbody>
            <xsl:call-template name="renderBody"/>
            </tbody>
        </table>
    </xsl:template>

    <xsl:template name="renderBody">
        <xsl:choose>
            <xsl:when test="$numericSort = 'true'">
                <xsl:for-each select="herbs/herb">
                    <xsl:sort select="number(*[name()=$sortField])" data-type="number" order="{$sortOrder}"/>
                    <xsl:call-template name="renderRow"/>
                </xsl:for-each>
            </xsl:when>
            <xsl:otherwise>
                <xsl:for-each select="herbs/herb">
                    <xsl:sort select="*[name()=$sortField]" data-type="text" order="{$sortOrder}"/>
                    <xsl:call-template name="renderRow"/>
                </xsl:for-each>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template name="renderRow">
        <tr>
            <td><xsl:value-of select="@id"/></td>
            <td>
                <div class="fw-bold mb-1"><xsl:value-of select="name"/></div>
                <div class="text-muted small"><xsl:value-of select="alias"/></div>
            </td>
            <td><span class="badge bg-success"><xsl:value-of select="category"/></span></td>
            <td>￥<xsl:value-of select="format-number(number(price), '0.00')"/></td>
            <td><xsl:value-of select="stock"/></td>
            <td><xsl:value-of select="origin"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>

