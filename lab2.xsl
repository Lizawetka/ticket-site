<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    
    <!-- Параметры сортировки, передаваемые из PHP -->
    <xsl:param name="sort_by">id</xsl:param>
    <xsl:param name="sort_order">ascending</xsl:param>

    <xsl:template match="/">
        <html>
        <head>
            <meta charset="UTF-8"/>
            <title>Расписание и рейсы</title>
        </head>
        <body>
            <div class="xml-table-wrapper">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Вид транспорта</th>
                            <th>Маршрут</th>
                            <th>Дата отправления</th>
                            <th>Цена (руб.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Выборка и сортировка элементов -->
                        <xsl:for-each select="transport_catalog/trip">
                            <xsl:sort select="*[name()=$sort_by]" order="{$sort_order}" data-type="text"/>
                            <tr>
                                <td><xsl:value-of select="id"/></td>
                                <td><xsl:value-of select="transport_type"/></td>
                                <td><xsl:value-of select="route"/></td>
                                <td><xsl:value-of select="date"/></td>
                                <td><strong><xsl:value-of select="price"/> ₽</strong></td>
                            </tr>
                        </xsl:for-each>
                    </tbody>
                </table>
            </div>
        </body>
        </html>
    </xsl:template>
</xsl:stylesheet>