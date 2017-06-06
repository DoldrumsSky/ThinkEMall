<?php
/**
 * Copyright (C) Alibaba Cloud Computing
 * All rights reserved.
 *
 * 版权所有 （C）阿里云计算有限公司
 */

namespace Aliyun\Common\Communication;

interface ServiceClientInterface {
	public function sendRequest(HttpRequest $request,ExecutionContext $context);
}
