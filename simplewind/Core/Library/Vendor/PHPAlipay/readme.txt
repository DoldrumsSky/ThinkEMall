��˵����

��demo����Ϊѧϰ�ο�ʹ�ã������ʵ��������п������ѹ���Ƕ��������Ŀ��ƽ̨�С�

�����л�����

PHP5.5������

��ҵ����ע�������

������notify_url�ļ���return_url�ļ������У�notify_url�ļ���Ҫ��д��ҵ�����߼����룬�����������������д��

�����֤�첽֪ͨ���ݣ�

1���̻���Ҫ��֤��֪ͨ�����е�out_trade_no�Ƿ�Ϊ�̻�ϵͳ�д����Ķ�����

2���ж�total_amount�Ƿ�ȷʵΪ�ö�����ʵ�ʽ����̻���������ʱ�Ľ�

3��У��֪ͨ�е�seller_id������seller_email) �Ƿ�Ϊ�ñʽ��׶�Ӧ�Ĳ�������һ���̻������ж��seller_id/seller_email��

4����֤�ӿڵ��÷���app_id


��Demoʹ���ֲ��
�����Ҫ˵��
pagepay
	buildermodel ---------- ��Ӧ�Ľӿڵ�bizcontentҵ��������з�װ����������jsonת�������ַ������θ��ѷ��㡣
	service->AlipayTradeService.php      ---------- ���нӿ���ʹ�õķ�����


AlipayTradeService.php �ļ��ڷ���˵��

1��SDK���󷽷�
aopclientRequestExecute($request,$ispage=false)
$request����Ӧ�ӿ�����Ķ���
$ispage���Ƿ�Ϊҳ����ת�����ֻ���վ֧���͵�����վ֧������Ϊҳ����ת����ѯ���˿����������ҳ����ת��

2��������վ֧���ӿڵķ���
pagePay($builder,$return_url,$notify_url)
$builder��ҵ�������ʹ��buildmodel�еĶ������ɡ�
$return_url��ͬ����ת��ַ
$notify_url���첽֪ͨ��ַ

3��������վ��ѯ�ӿ�
Query($builder)
$builder��ҵ�������ʹ��buildmodel�еĶ������ɡ�

4��������վ�˿�ӿ�
Refund($builder)
$builder��ҵ�������ʹ��buildmodel�еĶ������ɡ�

5��������վ�رսӿ�
Close($builder)
$builder��ҵ�������ʹ��buildmodel�еĶ������ɡ�

6��������վ�˿��ѯ�ӿ�
refundQuery($builder)
$builder��ҵ�������ʹ��buildmodel�еĶ������ɡ�

7��֧�������ص���Ϣ��ǩ
check($arr)
$arr���յ���֧����������Ϣ����

8����ӡ��־
writeLog($text)
$text��Ҫ��ӡ���ַ���