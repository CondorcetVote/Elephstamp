# API Reference Index

## CondorcetVote\ElephStamp
| Class Name | Description |
| ------------- | ------------- |
| [DetachedTimestampFile](ref/CondorcetVote/ElephStamp/DetachedTimestampFile/class_DetachedTimestampFile.md) | _A timestamp bound to the digest of a specific file — the content of an .ots proof file._ |
| [ElephStamp](ref/CondorcetVote/ElephStamp/ElephStamp/class_ElephStamp.md) | _The library entry point: submit timestamp requests to calendar servers and refresh receipts as they get confirmed._ |
| [FileToStamp](ref/CondorcetVote/ElephStamp/FileToStamp/class_FileToStamp.md) | _A piece of data to be timestamped, together with its privacy preference._ |
| [Receipt](ref/CondorcetVote/ElephStamp/Receipt/class_Receipt.md) | _A timestamp proof for one file — the object form of an .ots file._ |
| [Timestamp](ref/CondorcetVote/ElephStamp/Timestamp/class_Timestamp.md) | _A proof that one or more attestations commit to a message._ |

| Enum Name | Description |
| ------------- | ------------- |
| [Status](ref/CondorcetVote/ElephStamp/Status/enum_Status.md) | _Lifecycle state of a {@see Receipt}._ |

## CondorcetVote\ElephStamp\Attestation
| Class Name | Description |
| ------------- | ------------- |
| [BitcoinAttestation](ref/CondorcetVote/ElephStamp/Attestation/BitcoinAttestation/class_BitcoinAttestation.md) | _A commitment confirmed by the Bitcoin blockchain._ |
| [PendingAttestation](ref/CondorcetVote/ElephStamp/Attestation/PendingAttestation/class_PendingAttestation.md) | _A commitment recorded by a remote calendar, awaiting confirmation._ |
| [TimeAttestation](ref/CondorcetVote/ElephStamp/Attestation/TimeAttestation/class_TimeAttestation.md) | _A leaf of a timestamp proof: evidence attesting that a message existed prior to some point in time._ |
| [UnknownAttestation](ref/CondorcetVote/ElephStamp/Attestation/UnknownAttestation/class_UnknownAttestation.md) | _An attestation whose type this library does not understand (for example Litecoin or Ethereum)._ |


## CondorcetVote\ElephStamp\Calendar
| Class Name | Description |
| ------------- | ------------- |
| [CalendarResponse](ref/CondorcetVote/ElephStamp/Calendar/CalendarResponse/class_CalendarResponse.md) | _The outcome of contacting a single calendar._ |
| [CalendarWhitelist](ref/CondorcetVote/ElephStamp/Calendar/CalendarWhitelist/class_CalendarWhitelist.md) | _Allowlist deciding which calendar URIs {@see \CondorcetVote\ElephStamp\ElephStamp::upgrade()} is permitted to contact._ |
| [FakeCalendarClient](ref/CondorcetVote/ElephStamp/Calendar/FakeCalendarClient/class_FakeCalendarClient.md) | _In-memory calendar for tests and local integration environments._ |
| [HttpCalendarClient](ref/CondorcetVote/ElephStamp/Calendar/HttpCalendarClient/class_HttpCalendarClient.md) | _Calendar client backed by the Symfony HTTP client._ |


## CondorcetVote\ElephStamp\Exception
| Class Name | Description |
| ------------- | ------------- |
| [CalendarException](ref/CondorcetVote/ElephStamp/Exception/CalendarException/class_CalendarException.md) | _Thrown when a calendar server cannot be reached or returns an unexpected response (network error, non-200/404 status, oversized body, ...)._ |
| [InvalidInputException](ref/CondorcetVote/ElephStamp/Exception/InvalidInputException/class_InvalidInputException.md) | _Thrown when caller-supplied input is invalid: an unreadable file, an empty digest, an out-of-range value, and so on._ |
| [SerializationException](ref/CondorcetVote/ElephStamp/Exception/SerializationException/class_SerializationException.md) | _Thrown when reading or writing the OpenTimestamps binary format fails._ |
| [StampingException](ref/CondorcetVote/ElephStamp/Exception/StampingException/class_StampingException.md) | _Thrown when a stamp request cannot gather enough calendar attestations to satisfy the required threshold (the m-of-n policy)._ |


## CondorcetVote\ElephStamp\Merkle
| Class Name | Description |
| ------------- | ------------- |
| [MerkleTree](ref/CondorcetVote/ElephStamp/Merkle/MerkleTree/class_MerkleTree.md) | _Builds the merkle tree that binds several file timestamps to a single commitment, so one calendar submission covers them all._ |


## CondorcetVote\ElephStamp\Operation
| Class Name | Description |
| ------------- | ------------- |
| [Append](ref/CondorcetVote/ElephStamp/Operation/Append/class_Append.md) | _Append a fixed suffix to the message._ |
| [BinaryOperation](ref/CondorcetVote/ElephStamp/Operation/BinaryOperation/class_BinaryOperation.md) | _An operation that combines the message with a fixed argument (append/prepend)._ |
| [HashOperation](ref/CondorcetVote/ElephStamp/Operation/HashOperation/class_HashOperation.md) | _A cryptographic hash operation._ |
| [Hexlify](ref/CondorcetVote/ElephStamp/Operation/Hexlify/class_Hexlify.md) | _Convert the message to its lower-case hexadecimal representation._ |
| [Keccak256](ref/CondorcetVote/ElephStamp/Operation/Keccak256/class_Keccak256.md) | _Keccak-256, as used by Ethereum attestations._ |
| [Operation](ref/CondorcetVote/ElephStamp/Operation/Operation/class_Operation.md) | _A single edge in a timestamp proof tree._ |
| [Prepend](ref/CondorcetVote/ElephStamp/Operation/Prepend/class_Prepend.md) | _Prepend a fixed prefix to the message._ |
| [Reverse](ref/CondorcetVote/ElephStamp/Operation/Reverse/class_Reverse.md) | _Reverse the bytes of the message._ |
| [Ripemd160](ref/CondorcetVote/ElephStamp/Operation/Ripemd160/class_Ripemd160.md) | _RIPEMD-160._ |
| [Sha1](ref/CondorcetVote/ElephStamp/Operation/Sha1/class_Sha1.md) | _SHA-1._ |
| [Sha256](ref/CondorcetVote/ElephStamp/Operation/Sha256/class_Sha256.md) | _SHA-256, the default and recommended hash operation._ |
| [UnaryOperation](ref/CondorcetVote/ElephStamp/Operation/UnaryOperation/class_UnaryOperation.md) | _An operation that acts on the message alone, with no argument._ |


## CondorcetVote\ElephStamp\Random
| Class Name | Description |
| ------------- | ------------- |
| [CryptoRandomSource](ref/CondorcetVote/ElephStamp/Random/CryptoRandomSource/class_CryptoRandomSource.md) | _The default source, backed by a {@see Randomizer} over the cryptographically secure engine._ |
| [DeterministicRandomSource](ref/CondorcetVote/ElephStamp/Random/DeterministicRandomSource/class_DeterministicRandomSource.md) | _A reproducible source, backed by a {@see Randomizer} over a seeded engine._ |


## CondorcetVote\ElephStamp\Serialization
| Class Name | Description |
| ------------- | ------------- |
| [Deserializer](ref/CondorcetVote/ElephStamp/Serialization/Deserializer/class_Deserializer.md) | _Reader for the OpenTimestamps binary format._ |
| [Serializer](ref/CondorcetVote/ElephStamp/Serialization/Serializer/class_Serializer.md) | _Writer for the OpenTimestamps binary format._ |


