> CondorcetVote \ [ElephStamp](../readme.md) \ **Verify**
# Namespace: CondorcetVote\ElephStamp\Verify

## Classes

| Class Name | Description |
| ------------- | ------------- |
| [AnchorVerification](AnchorVerification/class_AnchorVerification.md) | _One Bitcoin attestation checked against the block it names._ |
| [BitcoinRpcBlockHeaderSource](BitcoinRpcBlockHeaderSource/class_BitcoinRpcBlockHeaderSource.md) | _Block headers from a Bitcoin node through its JSON-RPC interface: Bitcoin Core (or anything speaking the same protocol, such as a hosted RPC provider) answering getblockhash, getblockheader and getblo..._ |
| [BlockHeader](BlockHeader/class_BlockHeader.md) | _The 80-byte header of a Bitcoin block, or the part of it verification needs._ |
| [CrossCheckingBlockHeaderSource](CrossCheckingBlockHeaderSource/class_CrossCheckingBlockHeaderSource.md) | _Asks several sources and only answers when they all agree._ |
| [EsploraBlockHeaderSource](EsploraBlockHeaderSource/class_EsploraBlockHeaderSource.md) | _Block headers from any server speaking the Esplora HTTP API: mempool.space, blockstream.info, or a self-hosted instance._ |
| [FakeBlockHeaderSource](FakeBlockHeaderSource/class_FakeBlockHeaderSource.md) | _In-memory block headers for tests and local environments._ |
| [VerificationReport](VerificationReport/class_VerificationReport.md) | _The result of {@see Verifier::verify()}: the file check, one entry per Bitcoin attestation, and the conclusion drawn from them._ |
| [Verifier](Verifier/class_Verifier.md) | _Checks a receipt against the Bitcoin blockchain, through a {@see BlockHeaderSource}._ |

## Enums

| Enum Name | Description |
| ------------- | ------------- |
| [AnchorOutcome](AnchorOutcome/enum_AnchorOutcome.md) | _What checking one Bitcoin attestation against a block header gave._ |
| [Explorer](Explorer/enum_Explorer.md) | _The public block explorers this library knows how to talk to._ |
| [Verdict](Verdict/enum_Verdict.md) | _The overall conclusion of verifying a receipt._ |
